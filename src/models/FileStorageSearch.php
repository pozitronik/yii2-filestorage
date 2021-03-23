<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\models;

use yii\data\ActiveDataProvider;

/**
 * Class FileStorageSearch
 */
class FileStorageSearch extends FileStorage {
	public $name;
	public $path;
	public $model_name;
	public $model_key;
	public $at;
	public $tags;
	public $size;

	/**
	 * {@inheritDoc}
	 */
	public function rules():array {
		return [
			[['model_key', 'size'], 'integer'],
			[['name', 'path', 'model_name', 'at', 'delegate', 'tags'], 'safe']
		];
	}

	/**
	 * @param array $params
	 * @return ActiveDataProvider
	 */
	public function search(array $params):ActiveDataProvider {
		$this->load($params);
		$this->validate();
		$query = FileStorage::find();
		$dataProvider = new ActiveDataProvider([
			'query' => $query
		]);
		$dataProvider->setSort([
			'defaultOrder' => ['id' => SORT_DESC]
		]);

		if (!$this->validate()) return $dataProvider;
		return $dataProvider;
	}

}