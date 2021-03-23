<?php
declare(strict_types = 1);

use yii\web\View;

/**
 * @var View $this
 * @var string $inputId
 * @var string $fileInfo
 * @var string $input
 * @var string|false $downloadButton
 * @var string|false $versionsButton
 */

?>

<div id="<?= $inputId ?>-container" class="file-input-container">
	<div class="row">
		<div class="col-md-8">
			<label id="<?= $inputId ?>-info" for="<?= $inputId ?>" class="file-input-info">
				<?= $fileInfo ?>
			</label>
		</div>
		<div class="col-md-4">
			<div id="<?= $inputId ?>-input" class="file-input-input">
				<?php if (false !== $versionsButton): ?>
					<?= $versionsButton ?>
				<?php endif; ?>
				<?php if (false !== $downloadButton): ?>
					<?= $downloadButton ?>
				<?php endif; ?>
				<?php if (false !== $input): ?>
					<label for="<?= $inputId ?>" class="btn btn-success file-input-replacement">
						<i class="glyphicon glyphicon-upload"></i>
						<?= $input ?>
					</label>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
