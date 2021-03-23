<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\widgets\file_list;

use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Class FileListWidget
 * Показываем список файлов, ассоциированных с моделью
 *
 * @property Model $model Модель, для которой показываем файлы
 * @property string[] $tags Список тегов, по которым показываем файлы, все ассоцированные, если не задан
 * @property bool $allowVersions Отображать версии загрузки (отдельный popup)
 * @property bool $allowDownload Отображать кнопку загрузки файла
 */
class FileListWidget extends Widget {
	public $model;
	public $tags = [];
	public $allowVersions = true;
	public $allowDownload = true;

	/**
	 * Функция инициализации и нормализации свойств виджета
	 */
	public function init():void {
		parent::init();
		FileListWidgetAssets::register($this->getView());
	}

	/**
	 * {@inheritDoc}
	 */
	public function run():string {
		$fileStorage = new FileStorage([
			'model' => $this->model
		]);

		return $this->render('file_list', [
			'dataProvider' => new ArrayDataProvider(['allModels' => $fileStorage->getActualFiles($this->tags)]),
			'allowVersions' => $this->allowVersions,
			'allowDownload' => $this->allowDownload
		]);

	}

	/**
	 * @param FileStorage $file
	 * @return string
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public static function generateDownloadButton(FileStorage $file):string {
		if ((null === $uploadedFile = FileStorage::findModel($file->id)) || null === $uploadedFile->size) {
			return Html::tag('div', Html::tag('i', '', [
				'class' => 'fa fa-exclamation-triangle',
				'title' => 'Файл не найден!'
			]), [
				'class' => 'btn btn-danger file-list-download-not-found',
				'id' => "{$file->id}-download-not-found"
			]);
		}
		return FSModule::a(Html::tag('i', '', [
			'class' => 'fa fa-download',
			'title' => 'Скачать файл'
		]), ['index/download', 'id' => $uploadedFile->id], [
			'class' => 'btn btn-info file-list-download',
			'id' => "{$file->id}-download"
		]);
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 */
	public static function generateVersionsButton(FileStorage $file):string {
		return Html::button(Html::tag('i', '', [
				'class' => 'fa fa-history'
			]), [
				'class' => 'btn btn-default file-list-storage-versions',
				'onclick' => new JsExpression("jQuery('#file-storage-versions-{$file->id}').modal('show')")
			]).self::renderVersionsModal($file);
	}

	/**
	 * @param FileStorage $file
	 * @return string
	 */
	public static function renderVersionsModal(FileStorage $file):string {
		return Yii::$app->view->render('modalVersions', ['fileStorage' => $file], new static());
	}


}