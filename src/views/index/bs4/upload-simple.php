<?php
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedNamespaceInspection */
declare(strict_types = 1);

/**
 * @var View $this
 * @var FileTestModel $model
 */

use pozitronik\filestorage\models\test\FileTestModel;
use yii\bootstrap4\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

?>
<?php $form = ActiveForm::begin(); ?>
	<div class="panel">
		<div class="panel-container show">
			<div class="panel-content">
				<div class="col-md-12">
					<?= $form->field($model, 'uploadFileInstance')->fileInput()->label('Загрузка к тестовой модели') ?>
				</div>
			</div>
			<div class="panel-content">
				<div class="btn-group">
					<?= Html::submitButton('Загрузить', ['class' => 'btn btn-primary']) ?>
				</div>
			</div>
		</div>
	</div>
<?php ActiveForm::end(); ?>