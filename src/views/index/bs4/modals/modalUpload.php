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
use yii\bootstrap4\Modal;
use yii\bootstrap4\Html;
use yii\web\View;
use yii\bootstrap4\ActiveForm;

?>

<?php Modal::begin([
	'id' => "file-modal-upload",
	'size' => Modal::SIZE_LARGE,
	'title' => '<div class="modal-title">Загрузка файла:</div>',
	'footer' => Html::submitButton('<i class="fa fa-download"></i> Загрузить', ['class' => 'btn btn-success', 'form' => 'file-modal-upload-form']),//post button outside the form
	'clientOptions' => ['backdrop' => true]
]); ?>

<?php $form = ActiveForm::begin([
	'id' => 'file-modal-upload-form',
	'action' => FSModule::to(['index/modal-upload', 'id' => $fileStorage->id]),
	'method' => 'POST'
]); ?>
<div class="panel">
	<div class="panel-container show">
		<div class="panel-content">
			<div class="col-md-6">
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
