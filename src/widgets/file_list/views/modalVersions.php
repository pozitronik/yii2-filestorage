<?php
declare(strict_types = 1);

/**
 * @var FileStorage $fileStorage
 * @var View $this
 */

use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use pozitronik\helpers\Utils;
use yii\bootstrap\Modal;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

$versionsProvider = new ArrayDataProvider(['allModels' => $fileStorage->versions])
?>

<?php Modal::begin([
	'id' => "file-storage-versions-{$fileStorage->id}",
	'size' => Modal::SIZE_LARGE,
	'header' => '<div class="modal-title">История загрузок файла:</div>',
	'footer' => false,
	'clientOptions' => ['backdrop' => true]
]); ?>

<?= GridView::widget([
	'dataProvider' => $versionsProvider,
	'export' => false,
	'resizableColumns' => true,
	'responsive' => true,
	'summary' => "Найдена ".Utils::pluralForm($versionsProvider->totalCount, ['версия', 'версии', 'версий']),
	'columns' => [
		'versionIndex',
		[
			'class' => DataColumn::class,
			'attribute' => 'name',
			'value' => static function(FileStorage $model) {
				if (null === $model->size) {//файл не найден
					return Html::tag('i', '', ['class' => 'glyphicon glyphicon-exclamation-sign', 'style' => 'color: red']).$model->name;
				}
				return FSModule::a(Html::tag('i', '', [
						'class' => 'fa fa-download',
						'title' => 'Скачать файл'
					])." ".$model->name, ['index/download', 'id' => $model->id], [
					'class' => 'btn btn-info file-input-download',
				]);
			},
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'size',
			'format' => 'shortsize'
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
	]
]) ?>

<?php Modal::end(); ?>
