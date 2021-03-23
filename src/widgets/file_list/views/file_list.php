<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var FileStorage $fileStorageModel
 * @var ArrayDataProvider $dataProvider
 * @var bool $allowDownload
 * @var bool $allowVersions
 */

use pozitronik\filestorage\models\FileStorage;
use pozitronik\filestorage\widgets\file_list\FileListWidget;
use pozitronik\widgets\BadgeWidget;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

?>
<?= GridView::widget([
	'dataProvider' => $dataProvider,
	'tableOptions' => ['class' => 'table table-striped'],
	'layout' => '{items}{pager}{summary}',
	'columns' => [
		[
			'label' => 'Скачать',
			'value'	=> static function(FileStorage $model) {
				return FileListWidget::generateDownloadButton($model);
			},
			'visible' => $allowDownload,
			'format' => 'raw'
		],
		[
			'label' => 'Версии',
			'value'	=> static function(FileStorage $model) {
				return FileListWidget::generateVersionsButton($model);
			},
			'visible' => $allowVersions,
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'name',
			'value' => static function(FileStorage $model) {
				$name_block = (null === $model->size)?Html::tag('i', '', ['class' => 'fa fa-exclamation-triangle', 'style' => 'color: red']).$model->name:Html::tag("div", $model->name, ['class' => 'file-list-file-name', 'filetype' => pathinfo($model->name, PATHINFO_EXTENSION)]);
				return Html::tag("div", $model->model->getAttributeLabel(ArrayHelper::getValue($model->tags, '0')), ['class' => 'file-list-attribute-name']).$name_block;
			},
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'daddyName',
			'label' => 'Создатель',
			'value' => static function(FileStorage $model) {
				return BadgeWidget::widget([
					'models' => $model->relDaddy,
					'attribute' => 'name',
					'useBadges' => false,
					'badgePostfix' => static function() use ($model) {
						if (null === $model->delegate) return false;
						return " (от имени {$model->relDelegate->name})";
					}
				]);
			},
			'format' => 'raw'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'at',
			'format' => 'datetime'
		],
		[
			'class' => DataColumn::class,
			'attribute' => 'size',
			'format' => 'shortsize'
		]
	]
]) ?>