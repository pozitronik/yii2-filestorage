<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\widgets\file_input;

use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use pozitronik\helpers\BootstrapHelper;
use Throwable;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

/**
 * Class FileInputWidget
 *
 * @property null|string[] $tags Фильтрующие теги. Если не задано, используем имя атрибута
 * @property string $fileInfoMask Шаблон генерации описания загруженного файла. Допустимые токены {name},{at},{uploader} (остальное кодим по необходимости)
 * @property string $emptyFileInfo Строка информации для невыбранного файла
 * @property bool $allowUpload Отображать кнопку загрузки файла
 * @property bool $allowVersions Отображать версии загрузки (отдельный popup)
 * @property bool $allowDownload Отображать кнопку загрузки файла
 * @property bool $nameSubstitution При выборе файла подставлять его имя в строку информации
 *
 *
 * Виджет проверялся только с заданием через model+attribute, генерацию с name пока не трогал
 */
class FileInputWidget extends InputWidget {
	public string $fileInfoMask = "{name}<br/><span class='small'>загружен: {at}</span>";
	public string $emptyFileInfo = "<span class='text-warning'>Файл отсутствует</span>";
	public bool $allowUpload = true;
	public bool $allowDownload = true;
	public bool $allowVersions = true;
	public bool $nameSubstitution = true;

	public $options = ['class' => 'form-control'];

	private $_input_id;

	private bool $_isBs4 = false;

	/**
	 * @var int a counter used to generate unique input id for widgets.
	 * @internal
	 */
	public static $counter = 0;

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		return parent::getViewPath().DIRECTORY_SEPARATOR.($this->_isBs4?'bs4':'bs3');
	}

	/**
	 * Функция инициализации и нормализации свойств виджета
	 */
	public function init():void {
		parent::init();
		$this->_input_id = Html::getInputId($this->model, $this->attribute).static::$counter++;
		$this->options['id'] = $this->_input_id;
		FileInputWidgetAssets::register($this->getView());
		$this->_isBs4 = BootstrapHelper::isBs4();
		if ($this->allowUpload) {
			Html::addCssClass($this->options, 'form-control');//required by yii form filters

			/** Auto-set form enctype for file uploads */
			if (isset($this->field, $this->field->form) && !isset($this->field->form->options['enctype'])) {
				$this->field->form->options['enctype'] = 'multipart/form-data';
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function run():string {
		$fileStorage = new FileStorage([
			'model' => $this->model
		]);

		if ($this->nameSubstitution) {
			$this->options['onchange'] = new JsExpression("generateOnchangeAction($(this), $('#{$this->_input_id}-info'))");
		}

		if (null === $fileStorage->model_key) {//new ActiveRecord or model without key
			return $this->render('file_input', [
				'inputId' => $this->_input_id,
				'fileInfo' => $this->emptyFileInfo,
				'input' => $this->renderFileInputHtml(),
				'downloadButton' => false,
				'versionsButton' => false,
			]);
		}

		$files = $fileStorage->getActualFiles($this->tags??[$this->attribute]);//всегда возвращает массив, даже для единичного тега

		$renders = [];
		if ([] === $files && $this->allowUpload) {
			$renders[] = $this->render('file_input', [
				'inputId' => $this->_input_id,
				'fileInfo' => $this->emptyFileInfo,
				'input' => $this->allowUpload?$this->renderFileInputHtml():false,
				'downloadButton' => false,
				'versionsButton' => false,
			]);
		} elseif ([] === $files && !$this->allowUpload) {//нет загруженного файла, и только просмотр
			return $this->render('file_input', [
				'inputId' => $this->_input_id,
				'fileInfo' => $this->emptyFileInfo,
				'input' => false,
				'downloadButton' => false,
				'versionsButton' => false,
			]);
		} else {
			foreach ($files as $file) {
				$renders[] = $this->render('file_input', [
					'inputId' => $this->_input_id,
					'fileInfo' => $this->generateFileInfo($file),
					'input' => $this->allowUpload?$this->renderFileInputHtml():false,
					'downloadButton' => $this->allowDownload?$this->generateDownloadButton($file):false,
					'versionsButton' => $this->allowVersions?$this->generateVersionsButton($file):false,
				]);
				if ($this->allowVersions) {
					$renders[] = $this->renderVersionsModal($file);
				}
			}
		}

		return implode("", $renders);
	}

	/**
	 * @param array|null $options
	 * @return string
	 */
	protected function renderFileInputHtml(?array $options = null):string {
		if ($this->hasModel()) {
			return Html::activeHiddenInput($this->model, $this->attribute).Html::activeFileInput($this->model, $this->attribute, $options??$this->options);//к файловым атрибутам требуется добавлять одноименные скрытые атрибуты, чтобы они появлялись в посте
		}
		return Html::hiddenInput($this->name, $this->value).Html::fileInput($this->name, $this->value, $options??$this->options);
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 */
	private function generateFileInfo(FileStorage $file):string {
		return $this->replaceTokens($this->fileInfoMask, [
			'{name}' => $file->name,
			'{at}' => $file->at,
			'{uploader}' => $file->daddy,
		]);
	}

	/**
	 * @param string $where
	 * @param string[] $tokens
	 * @return string
	 * @noinspection CallableParameterUseCaseInTypeContextInspection
	 */
	protected function replaceTokens(string $where, array $tokens):string {
		/** @var string $token */
		foreach ($tokens as $token => $replace) {
			if (false !== strpos($where, $token)) {
				$where = str_replace($token, $replace, $where);
			}
		}
		return $where;
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	private function generateDownloadButton(FileStorage $file):string {
		if ((null === $uploadedFile = FileStorage::findModel($file->id)) || null === $uploadedFile->size) {
			return Html::tag('div', Html::tag('i', '', [
				'class' => $this->_isBs4?'fa fa-exclamation-triangle':'glyphicon glyphicon-exclamation-sign',
				'title' => 'Файл не найден!'
			]), [
				'class' => 'btn btn-danger file-input-download-not-found',
				'id' => "{$this->_input_id}-download-not-found"
			]);
		}
		return FSModule::a(Html::tag('i', '', [
			'class' => $this->_isBs4?'fa fa-download':'glyphicon glyphicon-download',
			'title' => 'Скачать файл'
		]), ['index/download', 'id' => $uploadedFile->id], [
			'class' => 'btn btn-info file-input-download',
			'id' => "{$this->_input_id}-download"
		]);
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 */
	private function generateVersionsButton(FileStorage $file):string {
		return Html::button(Html::tag('i', '', [
			'class' => $this->_isBs4?'fa fa-history':'glyphicon glyphicon-time'
		]), [
			'class' => 'btn btn-default file-input-storage-versions',
			'onclick' => new JsExpression("jQuery('#file-storage-versions-{$file->id}').modal('show')")
		]);
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 */
	private function renderVersionsModal(FileStorage $file):string {
		return $this->render('modalVersions', [
			'fileStorage' => $file
		]);
	}

}