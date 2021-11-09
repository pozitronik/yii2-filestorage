<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
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
use yii\bootstrap4\LinkPager;
use yii\data\ActiveDataProvider;
use yii\grid\ActionColumn;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\bootstrap4\Html;
use yii\i18n\Formatter;
use yii\web\JsExpression;
use yii\web\View;

$this->title = 'Файловый менеджер';
$this->params['breadcrumbs'][] = $this->title;
ModalHelperAsset::register($this);

?>
<div class="panel">
	<div class="panel-hdr">
		<div class="panel-content">
			<?= FSModule::a('Загрузить к простой модели', ['index/upload-simple'], ['class' => 'btn btn-success']) ?>
			<?= FSModule::a('Загрузить к AR-модели', ['index/upload-active-record'], ['class' => 'btn btn-success']) ?>
		</div>
	</div>
	<div class="panel-container show">
		<div class="panel-content">
			<?= GridView::widget([
				'filterModel' => $searchModel,
				'dataProvider' => $dataProvider,
				'pager' => [
					'class' => LinkPager::class
				],
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
							'view' => static function($url, $model, $key) {
								return Html::a('<i class="fa fa-eye"></i>', $url, [
									'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Просмотр']
								]);
							},
							'versions' => static function($url, $model, $key) {
								return Html::a('<i class="fa fa-tags"></i>', $url, [
									'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Версии']
								]);
							},
							'copy' => static function($url, $model, $key) {
								return Html::a('<i class="fa fa-copy"></i>', $url, [
									'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Редактирование']
								]);
							},
							'upload' => static function($url, $model, $key) {
								return Html::a('<i class="fa fa-upload"></i>', "#", [
									'onclick' => new JsExpression('AjaxModal("'.FSModule::to(['index/modal-upload', 'id' => $model->id]).'", "file-modal-upload")'),
									'data' => ['trigger' => 'hover', 'toggle' => 'tooltip', 'placement' => 'top', 'original-title' => 'Загрузка']
								]);
							}
						]
					],
					[
						'class' => DataColumn::class,
						'attribute' => 'name',
						'value' => static function(FileStorage $model) {
							if (null === $model->size) {//файл не найден
								return Html::tag('i', '', ['class' => 'fa fa-exclamation-triangle', 'style' => 'color: red']).$model->name;
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
</div>
