<?php
declare(strict_types = 1);

use pozitronik\filestorage\models\FileStorage;
use pozitronik\helpers\ArrayHelper;
use pozitronik\traits\traits\MigrationTrait;
use yii\db\Migration;

/**
 * m000000_000001_FileStorageTimestampsTimezones
 */
class m000000_000001_FileStorageTimestampsTimezones extends Migration {
	use MigrationTrait;

	/**
	 * @return string
	 */
	public static function mainTableName():string {
		return FileStorage::tableName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		if (null !== ArrayHelper::getValue($this->db->schema->typeMap, 'timestamptz')) {
			$this->alterColumn(self::mainTableName(), 'at', $this->timestamptz(0)->defaultExpression('CURRENT_TIMESTAMP')->notNull()->comment('Дата и время создания.'));
		} else {
			Yii::info('timestamptz column type is not supported bu DB schema, migration not applied.');
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		if (null !== ArrayHelper::getValue($this->db->schema->typeMap, 'timestamptz')) {
			$this->alterColumn(self::mainTableName(), 'at', $this->timestamp(0)->defaultExpression('CURRENT_TIMESTAMP')->notNull()->comment('Дата и время создания.'));
		} else {
			Yii::info('timestamptz column type is not supported bu DB schema, migration not applied.');
		}
	}
}