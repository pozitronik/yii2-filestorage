<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var ActiveDataProvider $dataProvider
 * @var FileStorageSearch $searchModel
 */

use pozitronik\filestorage\assets\ModalHelperAsset;
use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use pozitronik\filestorage\models\FileStorageSearch;
use pozitronik\widgets\BadgeWidget;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\bootstrap\Html;
use yii\i18n\Formatter;
use yii\web\JsExpression;
use yii\web\View;

$this->title = 'Файловый менеджер';
$this->params['breadcrumbs'][] = $this->title;
ModalHelperAsset::register($this);

?>
<div class="panel">
	<div class="panel-heading">
		<?= FSModule::a('Загрузить к простой модели', ['index/upload-simple'], ['class' => 'btn btn-success']) ?>
		<?= FSModule::a('Загрузить к AR-модели', ['index/upload-active-record'], ['class' => 'btn btn-success']) ?>
	</div>
	<div class="panel-body">
		<?= GridView::widget([
			'filterModel' => $searchModel,
			'dataProvider' => $dataProvider,
			'filterOnFocusOut' => false,
			'id' => 'filestorage-grid-index',
			'formatter' => [
				'class' => Formatter::class,
				'nullDisplay' => 'Отсутствует'
			],
			'columns' => [
				[
					'class' => ActionColumn::class,
					'template' => '{view} {versions} {copy} {upload}',
					'buttons' => [
						'versions' => static function($url, $model, $key) {
							return Html::a('<i class="glyphicon glyphicon-tags"></i>', $url);
						},
						'copy' => static function($url, $model, $key) {
							return Html::a('<i class="glyphicon glyphicon-copy"></i>', $url);
						},
						'upload' => static function($url, $model, $key) {
							return Html::a('<i class="glyphicon glyphicon-upload"></i>', "#", [
								'onclick' => new JsExpression('AjaxModal("'.FSModule::to(['index/modal-upload', 'id' => $model->id]).'", "file-modal-upload")')
							]);
						}
					]
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'name',
					'value' => static function(FileStorage $model) {
						if (null === $model->size) {//файл не найден
							return Html::tag('i', '', ['class' => 'glyphicon glyphicon-exclamation-sign', 'style' => 'color: red']).$model->name;
						}
						return FSModule::a($model->name, ['index/download', 'id' => $model->id]);
					},
					'format' => 'raw'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'path',
					'format' => 'raw'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'size',
					'format' => 'shortsize'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'model_name',
					'value' => static function(FileStorage $model) {
						return FSModule::a($model->model_name, ['index/model', 'id' => $model->id]);
					},
					'format' => 'raw'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'model_key',
					'format' => 'raw',
					'value' => static function(FileStorage $model) {
						return FSModule::a((string)$model->model_key, ['index/list', 'id' => $model->id]);
					},
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'tags',
					'value' => static function(FileStorage $model) {
						return BadgeWidget::widget([
							'items' => $model->tags,
							'subItem' => 'value',
							'useBadges' => true
						]);
					},
					'format' => 'raw'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'daddy',
					'label' => 'Создатель',
					'format' => 'raw'
				],
				[
					'class' => DataColumn::class,
					'attribute' => 'at',
					'format' => 'datetime'
				],
			],
			'rowOptions' => static function($record) {
				$class = '';
				if ($record['deleted']) {
					$class .= 'danger ';
				}
				return ['class' => $class];
			}
		]) ?>
	</div>
</div>
