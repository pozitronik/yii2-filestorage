<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\assets;

use yii\web\AssetBundle;

/**
 * Class ModalHelperAsset
 */
class ModalHelperAsset extends AssetBundle {

	/**
	 * {@inheritDoc}
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/modalHelper/';
		$this->js = [
			'js/modalHelper.js'
		];
		$this->css = [
			'css/modalHelper.css'
		];
		parent::init();

	}
}