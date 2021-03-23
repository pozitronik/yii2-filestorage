<?php
declare(strict_types = 1);

/**
 * @var FileStorage $fileStorage
 * @var Model $model
 * @var string $fileAttribute
 * @var string[] $tags
 * @var View $this
 */

use kartik\select2\Select2;
use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\models\FileStorage;
use pozitronik\filestorage\widgets\file_input\FileInputWidget;
use yii\base\Model;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

?>

<?php Modal::begin([
	'id' => "file-modal-upload",
	'size' => Modal::SIZE_LARGE,
	'header' => '<div class="modal-title">Загрузка файла:</div>',
	'footer' => Html::submitButton('<i class="glyphicon glyphicon-save"></i> Загрузить', ['class' => 'btn btn-success', 'form' => 'file-modal-upload-form']),//post button outside the form
	'clientOptions' => ['backdrop' => true]
]); ?>

<?php $form = ActiveForm::begin([
	'id' => 'file-modal-upload-form',
	'action' => FSModule::to(['index/modal-upload', 'id' => $fileStorage->id]),
	'method' => 'POST',
	'options' => ['enctype' => 'multipart/form-data']
]); ?>
<div class="hpanel">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-6">
				<label class="control-label">Файл</label>
				<?= $form->field($model, $fileAttribute)->widget(FileInputWidget::class) ?>
			</div>
			<div class="col-md-6">
				<label class="control-label">Произвольные теги</label>
				<?= Select2::widget([
					'data' => $tags,
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
</div>
<?php ActiveForm::end(); ?>

<?php Modal::end(); ?>
