<?php
declare(strict_types = 1);

/**
 * @var View $this
 * @var Model $model
 */

use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\widgets\file_list\FileListWidget;
use yii\base\Model;
use yii\web\View;

?>
<div class="panel">
	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<?= FileListWidget::widget([
					'model' => $model
				]) ?>
			</div>
		</div>
	</div>
	<div class="panel-footer">
		<div class="btn-group">
			<?= FSModule::a('Файловый менеджер', ['index/index'], ['class' => 'btn btn-info pull-left']) ?>
		</div>
	</div>
</div>