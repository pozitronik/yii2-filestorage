<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\migrations;

use pozitronik\filestorage\models\FileStorage;
use pozitronik\filestorage\models\FileTags;
use yii\db\Migration;

/**
 * Class m000000000000_FileStorageMigration
 */
class m000000_000000_FileStorageMigration extends Migration {
	/**
	 * @return string
	 */
	public static function mainTableName():string {
		return FileStorage::tableName();
	}

	/**
	 * @return string
	 */
	public static function tagsTableName():string {
		return FileTags::tableName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp() {
		$this->createTable(self::mainTableName(), [
			'id' => $this->primaryKey(),
			'name' => $this->string(255)->notNull(),
			'path' => $this->string(255)->notNull(),
			'model_name' => $this->string(255)->null(),
			'model_key' => $this->integer()->null(),
			'at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
			'daddy' => $this->integer()->defaultValue(null),
			'delegate' => $this->string(255)->null(),
			'deleted' => $this->boolean()->defaultValue(false)->notNull()
		]);

		$this->createIndex('model_name_model_key', self::mainTableName(), ['model_name', 'model_key']);
		$this->createIndex('daddy', self::mainTableName(), ['daddy']);
		$this->createIndex('deleted', self::mainTableName(), ['deleted']);
		$this->createIndex('path', self::mainTableName(), ['path'], true);

		$this->createTable(self::tagsTableName(), [
			'id' => $this->primaryKey(),
			'file' => $this->integer()->notNull(),
			'tag' => $this->string(255)->notNull()
		]);

		$this->createIndex('file_tag', self::tagsTableName(), ['file', 'tag'], true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown() {
		$this->dropTable(self::tagsTableName());
		$this->dropTable(self::mainTableName());
	}

}
