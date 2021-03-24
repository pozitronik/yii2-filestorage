<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\controllers;

use pozitronik\filestorage\models\FileStorage;
use pozitronik\filestorage\models\FileStorageSearch;
use pozitronik\filestorage\models\test\FileTestModel;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class IndexController
 */
class IndexController extends Controller {

	/**
	 * @return string
	 */
	public function actionIndex():string {
		$params = Yii::$app->request->queryParams;
		$searchModel = new FileStorageSearch();
		$dataProvider = $searchModel->search($params);
		return $this->render('index', compact('searchModel', 'dataProvider'));
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionView(int $id):string {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		return $this->render('view', [
			'fileStorageModel' => $fileStorage
		]);
	}

	/**
	 * Отдаёт список версий указанной загрузки
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionVersions(int $id):string {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		return $this->render('versions', [
			'fileStorageModel' => $fileStorage
		]);
	}

	/**
	 * Отдаёт список актуальных файлов, привязанных к модели указанной загрузки
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionModel(int $id):string {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		return $this->render('model', [
			'fileStorageModel' => $fileStorage
		]);
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionUploadSimple() {
		$fileTestModel = new FileTestModel();
		if (null !== Yii::$app->request->post($fileTestModel->formName())) {
			$fileTestModel->uploadFile();
			return $this->redirect(['index']);
		}

		return $this->render('upload-simple', [
			'model' => $fileTestModel,
		]);
	}

	/**
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionUploadActiveRecord() {
		$fileStorageModel = new FileStorage();
		if (null !== Yii::$app->request->post($fileStorageModel->formName())) {
			if (null !== $modelId = Yii::$app->request->post('model')) {
				$fileStorageModel = FileStorage::findModel($modelId);
			}
			$fileStorageModel->uploadFile(Yii::$app->request->post('tags', []));
			return $this->redirect(['index']);
		}

		return $this->render('upload-active-record', [
			'model' => $fileStorageModel,
			'models' => ArrayHelper::cmap(FileStorage::find()->active()->all(), 'id', ['id', 'name'], ": ")
		]);
	}

	/**
	 * @param int $id
	 * @throws Throwable
	 */
	public function actionDownload(int $id):void {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		/** @var FileStorage $fileStorage */
		$fileStorage->download();
	}

	/**
	 * @param int $id
	 * @return Response
	 * @throws Throwable
	 */
	public function actionCopy(int $id):Response {
		$fileTestModel = new FileTestModel();
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		/** @var FileStorage $fileStorage */
		$copiedFile = $fileTestModel->copyFile($fileStorage, ['copied_file']);
		return $this->redirect(['view', 'id' => $copiedFile->id]);
	}

	/**
	 * Все файлы в ассоциированной модели
	 * @param int $id
	 * @return string
	 * @throws Throwable
	 */
	public function actionList(int $id):string {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));
		/** @var FileStorage $fileStorage */
		return $this->render('list', [
			'model' => $fileStorage->model
		]);
	}

	/**
	 * Модалка загрузки файла
	 * @param int $id
	 * @return string|Response
	 * @throws Throwable
	 */
	public function actionModalUpload(int $id) {
		$fileStorage = FileStorage::findModel($id, new NotFoundHttpException('Файл не найден'));

		if (Yii::$app->request->isPost && [] !== $fileStorageModels = $fileStorage->model->uploadFile(Yii::$app->request->post('tags', []))) {
			return $this->redirect(['view', 'id' => ArrayHelper::getValue($fileStorageModels,'0.id')]);
		}
		/** @var FileStorage $fileStorage */
		if (Yii::$app->request->isAjax) {
			return $this->renderAjax('modals/modalUpload', [
				'fileStorage' => $fileStorage,
				'model' => $fileStorage->model,
				'fileAttribute' => 'uploadFileInstance',
				'tags' => $fileStorage->model->attributeLabels()
			]);
		}
		return $this->redirect(['index']);
	}
}