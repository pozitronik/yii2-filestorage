<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\widgets\file_list;

use yii\web\AssetBundle;

/**
 * Class FileListWidgetAssets
 */
class FileListWidgetAssets extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = ['css/file_list.css'];
		$this->js = ['js/file_list.js'];
		parent::init();
	}
}