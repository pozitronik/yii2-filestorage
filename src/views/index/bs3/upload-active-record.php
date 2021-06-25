<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
declare(strict_types = 1);

/**
 * @var View $this
 * @var ActiveRecord $model
 * @var array $models
 */

use kartik\select2\Select2;
use pozitronik\filestorage\widgets\file_input\FileInputWidget;
use yii\db\ActiveRecord;
use yii\bootstrap\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

?>
<?php $form = ActiveForm::begin(); ?>
	<div class="panel">
		<div class="panel-body">
			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model, 'uploadFileInstance')->widget(FileInputWidget::class) ?>
				</div>
				<div class="col-md-4">
					<label class="control-label">Выбранная модель (для тестов используются модель FileStorage)</label>
					<?= Select2::widget([
						'data' => $models,
						'name' => 'model'
					]) ?>
				</div>
				<div class="col-md-4">
					<label class="control-label">Произвольные теги</label>
					<?= Select2::widget([
						'data' => $model->attributeLabels(),
						'options' => [
							'placeholder' => 'Выберите или добавьте теги',
							'multiple' => true
						],
						'name' => 'tags',
						'pluginOptions' => [
							'tags' => true,
							'tokenSeparators' => [',', ' '],
							'maximumInputLength' => 10
						]
					]) ?>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<div class="btn-group">
				<?= Html::submitButton('Загрузить', ['class' => 'btn btn-primary']) ?>
			</div>
		</div>
	</div>
<?php ActiveForm::end(); ?>