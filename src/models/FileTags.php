<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\models;

use pozitronik\filestorage\FSModule;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\ModuleHelper;
use pozitronik\traits\traits\ActiveRecordTrait;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property FileStorage $fileStorage
 */
class FileTags extends ActiveRecord {
	use ActiveRecordTrait;

	/**
	 * {@inheritDoc}
	 */
	public static function tableName():string {
		return ArrayHelper::getValue(ModuleHelper::params(FSModule::class), 'tableNameTags', 'sys_file_storage_tags');
	}

	/**
	 * @param int $fileId
	 * @param string[] $tags
	 * @throws Exception
	 * @throws InvalidConfigException
	 */
	public static function setTags(int $fileId, array $tags):void {
		foreach ($tags as $tag) {
			self::addInstance(['file' => $fileId, 'tag' => $tag]);
		}
	}

	/**
	 * @param int $fileId
	 * @return array
	 */
	public static function getTags(int $fileId):array {
		return ArrayHelper::getColumn(self::find()->select('tag')->where(['file' => $fileId])->all(), 'tag');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['file', 'tag'], 'required'],
			[['tag'], 'string'],
			[['file'], 'integer'],
			[['file', 'tag'], 'unique', 'targetAttribute' => ['file', 'tag']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'id' => 'ID',
			'file' => 'Идентификатор файла',
			'tag' => 'Метка'
		];
	}

	/**
	 * @return ActiveQuery
	 */
	public function getFileStorage():ActiveQuery {
		return $this->hasOne(FileStorage::class, ['id' => 'file']);
	}

}