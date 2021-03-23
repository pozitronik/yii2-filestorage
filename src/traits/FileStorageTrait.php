<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\traits;

use pozitronik\filestorage\models\FileStorage;
use Throwable;
use yii\base\Model;
use yii\web\ServerErrorHttpException;
use yii\web\UploadedFile;

/**
 * Trait FileStorageTrait
 *
 * @property UploadedFile $uploadFileInstance
 */
trait FileStorageTrait {
	public $uploadFileInstance;

	/**
	 * Шорткат для загрузки файла по имени файлового атрибута сразу с привязкой к этому атрибуту
	 * @param string $attributeName
	 * @return FileStorage[]
	 * @throws Throwable
	 */
	public function uploadAttribute(string $attributeName):array {
		return $this->uploadFile([$attributeName], $attributeName);
	}

	/**
	 * Загружает файл в соответствующий модели каталог, возвращает полный путь или null в случае ошибки
	 * @param string[] $tags Массив тегов, прибиваемых к загрузке
	 * @param string $instanceName Параметр для переопределения имени инпута при необходимости
	 * @return FileStorage[] При успехе возвращает массив загруженных объектов хранения файла
	 * @throws Throwable
	 */
	public function uploadFile(array $tags = [], string $instanceName = 'uploadFileInstance'):array {
		$result = [];
		/** @var Model $this */
		if (null !== $uploadFileInstance = UploadedFile::getInstance($this, $instanceName)) {
			$result[] = $this->processUploadInstance($uploadFileInstance, $tags);
		} elseif ([] !== $uploadFileInstances = UploadedFile::getInstances($this, $instanceName)) {
			foreach ($uploadFileInstances as $uploadFileInstance) {
				$result[] = $this->processUploadInstance($uploadFileInstance, $tags);
			}
		}
		return $result;
	}

	/**
	 * @param UploadedFile $uploadFileInstance
	 * @param string[] $tags
	 * @return FileStorage
	 * @throws Throwable
	 */
	private function processUploadInstance(UploadedFile $uploadFileInstance, array $tags = []):FileStorage {
		$fileStorage = new FileStorage([
			'model' => $this,
			'uploadFileInstance' => $uploadFileInstance,
			'tags' => $tags
		]);

		if (!$fileStorage->save()) throw new ServerErrorHttpException("Не могу сохранить объект {$fileStorage->formName()}");

		return $fileStorage;
	}

	/**
	 * Вернуть все файлы модели. Если задан параметр $tags -- только те, которые попадают под эти теги
	 * @param null|string[] $tags
	 * @return FileStorage[]
	 * @throws Throwable
	 */
	public function files(?array $tags = null):array {
		$fileStorage = new FileStorage([
			'model' => $this
		]);

		return $fileStorage->getActualFiles($tags);
	}

	/**
	 * Копирует файл (физически и логически) из текущей записи к текущей модели
	 * @param FileStorage $fromStorage
	 * @param string[] $tags
	 * @return FileStorage
	 * @throws Throwable
	 */
	public function copyFile(FileStorage $fromStorage, array $tags = []):FileStorage {
		/** @var Model $this */
		return FileStorage::fromFile($fromStorage->path, $this, $tags, false);
	}

	/**
	 * Специально, чтобы можно было трейтить только к моделям!
	 * @see Model::formName()
	 */
	abstract public function formName();
}