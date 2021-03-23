<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\widgets\file_input;

use yii\web\AssetBundle;

/**
 * Class FileInputWidgetAssets
 */
class FileInputWidgetAssets extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = ['css/file_input.css'];
		$this->js = ['js/file_input.js'];
		parent::init();
	}
}