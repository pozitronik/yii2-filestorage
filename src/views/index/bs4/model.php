<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
declare(strict_types = 1);

/**
 * @var View $this
 * @var FileStorage $fileStorageModel
 */

use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use pozitronik\widgets\BadgeWidget;
use yii\data\ArrayDataProvider;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\bootstrap4\Html;
use yii\web\View;

$versionsProvider = new ArrayDataProvider(['allModels' => $fileStorageModel->getActualFiles()])

?>
<div class="panel">
	<div class="panel-container show">
		<div class="panel-content">
			<?= GridView::widget([
				'dataProvider' => $versionsProvider,
				'columns' => [
					[
						'class' => DataColumn::class,
						'attribute' => 'versionsCount',
						'value' => static function(FileStorage $model) {
							return FSModule::a((string)$model->versionsCount, ['index/versions', 'id' => $model->id]);
						},
						'format' => 'raw'
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
						'format' => 'raw'
					],
					[
						'class' => DataColumn::class,
						'attribute' => 'model_key',
						'format' => 'raw'
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
				]
			]) ?>
		</div>
		<div class="panel-content">
			<div class="btn-group">
				<?= FSModule::a('Скачать файл', ['index/download', 'id' => $fileStorageModel->id], ['class' => 'btn btn-warning pull-left']) ?>
				<?= FSModule::a('Загрузить ещё чего-нибудь', ['index/upload-simple'], ['class' => 'btn btn-success pull-left']) ?>
				<?= FSModule::a('Файловый менеджер', ['index/index'], ['class' => 'btn btn-info pull-left']) ?>
			</div>
		</div>
	</div>
</div>