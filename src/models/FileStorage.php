<?php
declare(strict_types = 1);

namespace pozitronik\filestorage\models;

use pozitronik\core\helpers\ModuleHelper;
use pozitronik\core\traits\ARExtended;
use pozitronik\filestorage\FSModule;
use pozitronik\filestorage\traits\FileStorageTrait;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\PathHelper;
use pozitronik\helpers\ReflectionHelper;
use pozitronik\helpers\Utils;
use ReflectionException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\UnknownClassException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\ActiveRecordInterface;
use yii\helpers\BaseFileHelper;
use yii\web\UploadedFile;

/**
 * Class FileStorage
 * Управление файлами для Yii2.
 * - Файл может быть привязан к экземпляру любой модели
 * - Файл может быть привязан к атрибуту любой модели через теги
 * - Поддерживается версионность загрузок: для набора [модель, [список тегов]] может существовать только один актуальный файл. При добавлении к модели файла с тем же набором тегов, он становится актуальным, а предыдущие файлы можно забрать, как версии.
 *
 * @property int $id
 * @property string $name -- оригинальное имя файла
 * @property string $path -- сохранённое имя файла
 * @property null|string $model_name -- связанная модель
 * @property null|int $model_key -- ключ связанной модели
 * @property-read string $at -- дата загрузки //current_timestamp
 * @property null|int $daddy -- id загрузившего пользователя
 * @property null|int $delegate -- для совместимости с tws: id делегировавшего юзера
 * @property bool $deleted -- флаг удаления
 * @property null|ActiveRecord|Model $model -- объект, к которому привязан файл. Предполагается, что это ActiveRecord, но может быть любая модель (в этом случае привязка файлов может работать только по тегам, без ключей)
 * @property string[] $tags -- набор тегов, которыми помечен файл
 * @property FileTags[] $relFileTags -- связь с таблицей тегов
 * @property-read string $uploadDir -- в зависимости от глобальных настроек и привязанной модели отдаёт путь загрузки
 * @property-read null|int $size -- размер файла в байтах. В текущей реализации запрашивается из ФС. Если файл отсутствует/недоступен, равно null
 *
 *
 * @property-read FileStorage[] $versions -- массив всех версий загрузки
 * @property-read int $versionIndex -- индекс версии загрузки (где 0 -- актуальная)
 * @property-read int $versionsCount -- количество версий файла
 * @property-read FileStorage[] $allModelUploads -- массив всех загрузок, привязанных к этой модели
 *
 * @property-read FileTags[] $allModelTags -- все модели тегов, под которыми загружались файлы к этой модели
 * @property UploadedFile $uploadFileInstance -- инстанс загрузки.
 *
 */
class FileStorage extends ActiveRecord {
	use FileStorageTrait;
	use ARExtended;

	public const CURRENT_FILE = 0;

	public $base_dir = '@app/web/uploads/';
	public $models_subdirs = true;
	public $name_subdirs_length = 2;

	private $_uploadFileInstance;//UploadedFile
	private $_model;//храним загруженный экземпляр модели, чтобы не перевычислять на каждое обращение
	private $_tags = [];
	private $_filename;

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		$this->base_dir = ArrayHelper::getValue(ModuleHelper::params(FSModule::class), 'base_dir', $this->base_dir);
		$this->models_subdirs = ArrayHelper::getValue(ModuleHelper::params(FSModule::class), 'models_subdirs', $this->models_subdirs);
		$this->name_subdirs_length = ArrayHelper::getValue(ModuleHelper::params(FSModule::class), 'name_subdirs_length', $this->name_subdirs_length);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName():string {
		return ArrayHelper::getValue(ModuleHelper::params(FSModule::class), 'tableName', 'sys_file_storage');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules():array {
		return [
			[['name', 'path', 'model_name'], 'string', 'max' => 255],
			[['name', 'path'], 'required'],
			[['path'], 'unique'],
			[['at'], 'safe'],//current_timestamp
			[['daddy', 'delegate', 'model_key'], 'integer'],
			[['daddy'], 'default', 'value' => Yii::$app->user->id, 'isEmpty' => static function($value) {
				return empty($value);
			}],
			[['delegate'], 'default', 'value' => static function($value) {
				return null;
			}],
			[['deleted'], 'boolean'],
			[['deleted'], 'default', 'value' => false]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels():array {
		return [
			'name' => 'Оригинальное имя',
			'path' => 'Путь в файловой системе',
			'model_name' => 'Связанный класс',
			'model_key' => 'Ключ модели',
			'at' => 'Время добавления',
			'size' => 'Размер',
			'daddy' => 'Создатель',
			'delegate' => 'Делегат',
			'tags' => 'Теги',
			'versions' => 'Версии',
			'versionIndex' => 'Индекс версии',
			'versionsCount' => 'Количество версий файла'
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function afterSave($insert, $changedAttributes):void {
		parent::afterSave($insert, $changedAttributes);
		FileTags::setTags($this->id, $this->tags);
	}

	/**
	 * Скачиваем текущий выбранный файл
	 */
	public function download():void {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$this->name.'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.$this->size);
		header('Accept-Ranges: bytes');
		header('Cache-Control: private');
		header('Pragma: private');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		readfile($this->path);
	}

	/**
	 * @param string $name
	 * @param string $path
	 * @param Model|null $model
	 * @param string|string[] $tags
	 * @return null|self
	 */
	public static function addFile(string $name, string $path, ?Model $model = null, $tags = []):?self {
		$fileStorage = new self(compact('model', 'tags', 'name', 'path'));
		return $fileStorage->save()?$fileStorage:null;
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 */
	public function getUploadDir():string {
		if (null === $this->model) return Yii::getAlias($this->base_dir);
		$dir = Yii::getAlias($this->models_subdirs?"$this->base_dir{$this->model->formName()}":$this->base_dir);
		if (0 !== $this->name_subdirs_length) {
			$dir .= DIRECTORY_SEPARATOR.mb_substr($this->name, 0, $this->name_subdirs_length);
		}
		return $dir;
	}

	/**
	 * @return ActiveRecordInterface|Model|object|null
	 * @throws ReflectionException
	 * @throws InvalidConfigException
	 * @throws UnknownClassException
	 */
	public function getModel():?object {
		if (null !== $this->_model) return $this->_model;
		if (null === $this->model_name) return null;
		if (null === $object = ReflectionHelper::LoadClassByName($this->model_name, null, false)) return null;
		if ($object instanceof ActiveRecordInterface && null !== $this->model_key) {
			$this->_model = $object::findOne($this->model_key);
		} else {
			$this->_model = $object;
		}
		return $this->_model;
	}

	/**
	 * @param Model|null $model
	 * @throws ReflectionException
	 * @throws Throwable
	 */
	public function setModel(?Model $model):void {
		if (null === $model) {
			$this->model_name = null;
			$this->model_key = null;
		} else {
			$this->model_name = get_class($model);
			if (ReflectionHelper::IsInSubclassOf(ReflectionHelper::New($model), [ActiveRecord::class])) {
				/** @var ActiveRecord $model */
				$primaryKeys = $model::primaryKey();
				if (!isset($primaryKeys[0])) $this->model_key = null;
				$this->model_key = ArrayHelper::getValue($model, $primaryKeys[0]);
			}

		}
	}

	/**
	 * Теги по имени атрибута загрузки в модели
	 * @param string $attributeName
	 * @return array
	 * @throws Throwable
	 */
	public function generateTags(string $attributeName):array {
		return [ArrayHelper::getValue($this->model->attributeLabels(), $attributeName, $attributeName)];
	}

	/**
	 * @return string[]
	 */
	public function getTags():array {
		if (!$this->isNewRecord && [] === $this->_tags) $this->_tags = FileTags::getTags($this->id);
		return $this->_tags;
	}

	/**
	 * @param string[] $tags
	 */
	public function setTags(array $tags):void {
		$this->_tags = $tags;
	}

	/**
	 * @return null|int
	 */
	public function getSize():?int {
		try {
			return (false === $filesize = filesize($this->path))?null:$filesize;
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable $t) {
			return null;
		}
	}

	/**
	 * @return ActiveQuery
	 */
	public function getRelFileTags():ActiveQuery {
		return $this->hasMany(FileTags::class, ['file' => 'id']);
	}

	/**
	 * Массив всех версий загрузки
	 * @return FileStorage[]
	 */
	public function getVersions():array {
		return self::find()
			->active()
			->joinWith(['relFileTags'])
			->where([
				'model_name' => $this->model_name,
				'tag' => $this->tags
			])
			->andFilterWhere(['model_key' => $this->model_key])//если файл был привязан не к AR-модели, то ключ не нужно учитывать
			->orderBy(['at' => SORT_DESC])
			->all();
	}

	/**
	 * Количество версий загрузки
	 * @return int
	 */
	public function getVersionsCount():int {
		return (int)self::find()
			->active()
			->joinWith(['relFileTags'])
			->where([
				'model_name' => $this->model_name,
				'tag' => $this->tags
			])
			->andFilterWhere(['model_key' => $this->model_key])
			->count();
	}

	/**
	 * индекс версии загрузки (где 0 -- актуальная)
	 * @return int|null
	 */
	public function getVersionIndex():?int {
		return (false === $index = array_search($this->id, ArrayHelper::getColumn($this->versions, 'id'), true))?null:$index;//на SQL получается геморно
	}

	/**
	 * Вернуть вообще все загрузки, подвязанные к этой модели
	 * @return FileStorage[]
	 */
	public function getAllModelUploads():array {
		$query = self::find()
			->active()
			->joinWith(['relFileTags'])
			->where(['model_name' => $this->model_name])
			->andFilterWhere(['model_key' => $this->model_key])
			->orderBy(['at' => SORT_DESC]);
		return $query->all();
	}

	/**
	 * Вернуть все модели тегов, под которыми загружались файлы к этой модели
	 * @return FileTags[]
	 */
	public function getAllModelTags():array {
		return FileTags::find()
			->where(['file' => ArrayHelper::getColumn($this->allModelUploads, 'id')])
			->all();
	}

	/**
	 * Вернуть все АКТУАЛЬНЫЕ файлы модели. Если задан параметр $tags -- только те, которые попадают под эти теги
	 * Я не сумел написать выборку на голом SQL. Нужно будет вернуться к этой задаче.
	 * @param string[] $tags - если задано, то отфильтровать по тегам
	 * @return FileStorage[]
	 * @throws Throwable
	 */
	public function getActualFiles(array $tags = []):array {
		$lastVersions = [];
		foreach ($this->getAllModelTags() as $tag) {
			$lastVersions[] = ArrayHelper::getValue($tag->fileStorage->versions, '0.id');
		}

		$query = self::find()
			->active()
			->where([$this::tableName().'.id' => array_unique($lastVersions)])
			->orderBy(['at' => SORT_DESC]);
		if ([] !== $tags) {
			$query->joinWith(['relFileTags'])->andWhere(['tag' => $tags]);
		}

		return $query->all();
	}

	/**
	 * @return null|UploadedFile
	 */
	public function getUploadFileInstance():?UploadedFile {
		return $this->_uploadFileInstance;
	}

	/**
	 * @param UploadedFile $uploadFileInstance
	 */
	public function setUploadFileInstance(UploadedFile $uploadFileInstance):void {
		$this->_uploadFileInstance = $uploadFileInstance;
	}

	/**
	 * @inheritDoc
	 */
	public function save($runValidation = true, $attributeNames = null):bool {
		if ($this->isNewRecord && null === $this->uploadFileInstance) {
			$this->addError('uploadFileInstance', "Не задан объект загрузки");
			return false;
		}

		$this->name = $this->name??pathinfo($this->uploadFileInstance->name, PATHINFO_BASENAME);//если задано имя - сохраняем загрузку с ним, иначе выковыриваем из загрузки

		if (!PathHelper::CreateDirIfNotExisted($this->uploadDir)) {
			$this->addError('uploadDir', "Не могу создать каталог {$this->uploadDir}");
			return false;
		}

		$fileName = BaseFileHelper::normalizePath($this->uploadDir.DIRECTORY_SEPARATOR.PathHelper::ChangeFileName($this->uploadFileInstance->name, pathinfo($this->uploadFileInstance->name, PATHINFO_FILENAME)."_".Utils::random_str(16)));//файл сохраняется с оригинальным именем и случайным постфиксом, во избежание коллизий
		if (!$this->uploadFileInstance->saveAs($fileName)) {
			$this->addError('uploadFileInstance', "Не могу сохранить файл {$fileName}");
			return false;
		}
		$this->path = $fileName;
		return (parent::save($runValidation, $attributeNames) && $this->refresh());

	}

	/**
	 * save мы перекрыли, но иногда требуется взаимодействие с AR
	 * @param bool $runValidation
	 * @param null|array $attributeNames
	 * @return bool
	 */
	public function saveRecord(bool $runValidation = true, ?array $attributeNames = null):bool {
		return (parent::save($runValidation, $attributeNames) && $this->refresh());
	}

	/**
	 * Перемещает файл по правилам размещения, заданным модулю
	 * (например, если правила поменялись, или запись была перенсена из другого источника)
	 * @param bool $move - true: переместить файл, false - скопировать
	 * @return bool
	 * @throws Throwable
	 */
	public function reallocate(bool $move = true):bool {
		$this->clearErrors();
		$oldFileName = $this->path;
		$newFileName = BaseFileHelper::normalizePath($this->uploadDir.DIRECTORY_SEPARATOR.PathHelper::ChangeFileName($this->path, pathinfo($this->name, PATHINFO_FILENAME)."_".Utils::random_str(16)));//файл сохраняется с оригинальным именем и случайным постфиксом, во избежание коллизий

		if (!file_exists($oldFileName)) {
			$this->addError('path', "Файл по расположению {$oldFileName} не существует");
			$this->path = $newFileName;//запись меняется без перемещений
			return $this->saveRecord();
		}

		if (file_exists($newFileName)) {
			$this->addError('path', "Файл по расположению {$newFileName} уже существует");
			return false;
		}

		if (!PathHelper::CreateDirIfNotExisted($this->uploadDir)) {
			$this->addError('uploadDir', "Не могу создать каталог {$this->uploadDir}");
			return false;
		}

		if ($move) {
			if (!rename($oldFileName, $newFileName)) {
				$this->addError('path', "Не могу переместить файл {$oldFileName} в {$newFileName}");
				return false;
			}
		} elseif (!copy($oldFileName, $newFileName)) {
			$this->addError('path', "Не могу копировать файл {$oldFileName} в {$newFileName}");
			return false;
		}

		$this->path = $newFileName;
		return $this->saveRecord(false);
	}

	/**
	 * Создаёт запись из физического файла, копируя или перемещая файл по правилам моделя
	 * @param string $fileName
	 * @param Model|null $model
	 * @param array $tags
	 * @param bool $move
	 * @return static
	 * @throws Throwable
	 */
	public static function fromFile(string $fileName, ?Model $model, array $tags, bool $move = true):self {
		$newFileStorage = new self([
			'name' => pathinfo($fileName, PATHINFO_BASENAME),
			'model' => $model,
			'tags' => $tags
		]);

		$newFileName = BaseFileHelper::normalizePath($newFileStorage->uploadDir.DIRECTORY_SEPARATOR.PathHelper::ChangeFileName($fileName, pathinfo($fileName, PATHINFO_FILENAME)."_".Utils::random_str(16)));//файл сохраняется с оригинальным именем и случайным постфиксом, во избежание коллизий

		if (!file_exists($fileName)) {
			$newFileStorage->addError('path', "Файл по расположению {$fileName} не существует");
		}

		if (file_exists($newFileName)) {
			$newFileStorage->addError('path', "Файл по расположению {$newFileName} уже существует");
		}

		if (!PathHelper::CreateDirIfNotExisted($newFileStorage->uploadDir)) {
			$newFileStorage->addError('uploadDir', "Не могу создать каталог {$newFileStorage->uploadDir}");
		}

		if ($newFileStorage->hasErrors()) return $newFileStorage;

		if ($move) {
			if (!rename($fileName, $newFileName)) {
				$newFileStorage->addError('path', "Не могу переместить файл {$fileName} в {$newFileName}");
			}
		} elseif (!copy($fileName, $newFileName)) {
			$newFileStorage->addError('path', "Не могу копировать файл {$fileName} в {$newFileName}");
		}

		$newFileStorage->path = $newFileName;

		if (!$newFileStorage->hasErrors() && $newFileStorage->saveRecord()) {
			$newFileStorage->refresh();
		}
		return $newFileStorage;
	}

}