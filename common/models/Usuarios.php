<?php

namespace common\models;

use yii\db\ActiveQuery;

use backend\components\PermisosUsuarios;

use \yii\web\IdentityInterface;

/**
 * This is the model class for table "usuarios".
 *
 * @property int $id
 * @property string $expulsado
 * @property string $nombre
 * @property string $password
 * @property string $correo
 * @property int $nacionalidad_id
 * @property string $tag
 * @property bool $verificado
 *
 * @property Jugadores $jugadores
 * @property Nacionalidades $nacionalidad
 */
class Usuarios extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * Normas aceptadas o no.
     * @var bool
     */
    public $normas;

    /**
     * TAG real del jugador en ClashRoyale
     * @var string
     */
    public $tag;

    /**
     * Segundo campo de password para actualizacion de password
     * @var string
     */
    public $password_repeat;

    /**
     * Escenario de invitar a un nuevo usuario.
     * @var string
     */
    const ESCENARIO_INVITAR = 'invitar';

    /**
     * Escenario de verificar a un usuario concreto.
     * @var string
     */
    const ESCENARIO_VERIFICAR = 'verificar';

    /**
     * Escenario de que un usuario envía una nueva invitación a los administradores.
     * @var string
     */
    const ESCENARIO_UNETE = 'unete';

    /**
     * Escenario de que un usuario actualiza su perfil.
     * @var string
     */
    const ESCENARIO_PERFIL = 'perfil';

    /**
     * Escenario de que un usuario actualiza su perfil.
     * @var string
     */
    const ESCENARIO_ACEPTAR_SOLICITUD = 'aceptar-solicitud';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'usuarios';
    }

    public function scenarios()
    {
        $escenarios = parent::scenarios();
        $escenarios[self::ESCENARIO_INVITAR] = array_merge($escenarios['default'], []);
        $escenarios[self::ESCENARIO_ACEPTAR_SOLICITUD] = array_merge($escenarios['default'], []);

        return $escenarios;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['nombre', 'correo', 'nacionalidad_id', 'password', 'activo', 'normas', 'zona_horaria_id'], 'trim'],
            [['nombre', 'correo', 'nacionalidad_id', 'zona_horaria_id'], 'required'],
            [['nombre'], 'string', 'min' => 4],
            [['password'], 'required', 'on' => [self::SCENARIO_DEFAULT, self::ESCENARIO_VERIFICAR]],
            [['password_repeat'], function ($attribute, $params, $validator) {
                if ($this->password && $this->$attribute == null) {
                    $this->addError($attribute, 'El campo de verificación de contraseña no puede estar vacío.');
                }
            }, 'on' => self::ESCENARIO_PERFIL, 'skipOnEmpty' => false],
            [['password_repeat'], 'default', 'value' => $this->password, 'on' => self::ESCENARIO_PERFIL, 'skipOnError' => true],
            [['password_repeat'], 'compare', 'compareAttribute'=> 'password', 'on' => self::ESCENARIO_PERFIL, 'skipOnError' => true],
            [['nacionalidad_id', 'jugador_id', 'zona_horaria_id'], 'default', 'value' => null],
            [['nacionalidad_id', 'zona_horaria_id'], 'integer'],
            [['activo'], 'boolean'],
            [['nombre', 'password', 'access_token', 'auth_key', 'verificado'], 'string', 'max' => 255],
            [['correo'], 'email'],
            [['correo', 'nombre'], 'unique'],
            [['nacionalidad_id'], 'exist', 'skipOnError' => true, 'targetClass' => Nacionalidades::className(), 'targetAttribute' => ['nacionalidad_id' => 'id']],
            [['zona_horaria_id'], 'exist', 'skipOnError' => true, 'targetClass' => ZonasHorarias::className(), 'targetAttribute' => ['zona_horaria_id' => 'id']],
            [['normas'], 'required', 'on' => self::ESCENARIO_UNETE],
            [['tag'], 'required', 'on' => self::ESCENARIO_UNETE],
            [['normas'], 'boolean', 'on' => self::ESCENARIO_UNETE],
            [['normas'], 'compare', 'compareValue' => true, 'message' => 'Las normas deben ser aceptadas obligatoriamente', 'on' => self::ESCENARIO_UNETE],
            [['tag'], function ($attribute, $params, $validator) {
                if (!$this->hasErrors()) {
                    $usuarioConTag  = null;
                    $jugadorBuscado = Jugadores::find()->where(['tag' => $this->$attribute])->one();

                    if ($jugadorBuscado != null) {
                        $usuarioConTag = self::find()
                                             ->where(['jugador_id' => $jugadorBuscado->id])
                                             ->one();
                    }

                    if ($usuarioConTag == null) {
                        if ($jugadorBuscado == null) {
                            $jugadorBuscado = Jugadores::findAPI('jugador', [
                                'tag' => [
                                    $this->tag
                                ]
                            ]);

                            $jugadorBuscado = (!empty($jugadorBuscado) ? $jugadorBuscado[0] : $jugadorBuscado);
                        }

                        if ($jugadorBuscado == null) {
                            $this->addError($attribute, 'No existe un jugador con ese TAG');
                        } else {
                            $this->jugador_id = $jugadorBuscado->id;
                        }
                    } else {
                        $this->addError($attribute, 'Ya existe un usuario con ese TAG asociado');
                    }
                }
            }, 'skipOnEmpty' => true],
            [['jugador_id'], 'exist', 'skipOnError' => true, 'targetClass' => Jugadores::className(), 'targetAttribute' => ['jugador_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return array_merge(parent::attributes(), ['normas', 'tag']);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre de usuario',
            'password' => 'Contraseña',
            'correo' => 'Correo',
            'nacionalidad_id' => 'Nacionalidad',
            'verificado' => 'Verificado',
            'normas' => 'Acepto las normas',
        ];
    }

    /**
     * Comprueba si un usuario está activo o no.
     * @return bool Activo o no.
     */
    public function getEstaActivo()
    {
        return $this->activo === true;
    }

    /**
     * Comprueba si un usuario está verificado mediante el correo o no.
     * @return bool Verificado o no.
     */
    public function getEstaVerificado()
    {
        return $this->verificado === null;
    }

    /**
     * Query de usuarios válidos para el logueo.
     * @return ActiveQuery ActiveQuery
     */
    public static function findLoginQuery()
    {
        return static::find()
                     ->where(['activo' => true])
                     ->andWhere('verificado is null')
                     ->andWhere('expulsado is null');
    }

    /**
     * Query de usuarios válidos para el logueo.
     * @return ActiveQuery ActiveQuery
     */
    public static function findLoginExpulsadosQuery()
    {
        return static::find()
                     ->where(['activo' => true])
                     ->andWhere('verificado is null');
    }

    /**
     * Usuarios pendientes de verificar.
     * @return Usuarios Usuarios no verificados.
     */
    public static function pendientes()
    {
        return static::find()
                     ->where(['activo' => false])
                     ->orWhere('verificado is not null')
                     ->all();
    }

    /**
     * Usuarios válidos (ya en el equipo)
     * @return Usuarios
     */
    public static function validos()
    {
        return static::findLoginQuery()
                     ->andWhere('jugador_id is not null')
                     ->all();
    }

    /**
     * Buscar un usuario por su nombre.
     * @param  string $nombre Nombre del usuario.
     * @return Usuarios       Usuario buscado.
     */
    public static function findByNombre(string $nombre)
    {
        return static::findOne(['nombre' => $nombre]);
    }

    /**
     * Busca si existe el usuario con el codigo de verificación x y lo devuelve si existe.
     * @param  string $auth Código de autentificación proporcionado por el correo de verificación.
     * @return Usuarios     Usuario verificado buscado.
     */
    public static function findByVerificado($auth)
    {
        return static::findOne(['verificado' => $auth]);
    }

    /**
      * Finds an identity by the given ID.
      *
      * @param string|int $id the ID to be looked for
      * @return IdentityInterface|null the identity object that matches the given ID.
      */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id]);
    }

     /**
      * Finds an identity by the given token.
      *
      * @param string $token the token to be looked for
      * @param string|null $type
      * @return IdentityInterface|null the identity object that matches the given token.
      */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

     /**
      * @return int|string current user ID
      */
    public function getId()
    {
        return $this->id;
    }

     /**
      * @return string current user auth key
      */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

     /**
      * @param string $authKey
      * @return bool if auth key is valid for current user
      */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Valida una contraseña con seguridad.
     * @param  string $password Contraseña a verificar.
     * @return bool             Es correcta o no.
     */
    public function validatePassword(string $password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password);
    }

    public function expulsar()
    {
        $this->expulsado = date('Y-m-d H:i:s');
        $this->save();
    }

    public function quitarExpulsion()
    {
        $this->expulsado = null;
        $this->save();
    }

    public function cambiarRol($rolId)
    {
        $rol = Roles::findOne($rolId);

        if ($rol) {
            $nuevoUsuarioRol = UsuariosRoles::find()
                                            ->where(['usuario_id' => $this->id])
                                            ->one();

            if ($nuevoUsuarioRol) {
                $nuevoUsuarioRol->rol_id = $rol->id;
            } else {
                $nuevoUsuarioRol = new UsuariosRoles([
                    'usuario_id' => $this->id,
                    'rol_id' => $rolId
                ]);
            }

            if ($nuevoUsuarioRol->save()) {
                PermisosUsuarios::down();
                PermisosUsuarios::up();
            }
        }
    }

    public function getEstaExpulsado()
    {
        return $this->expulsado !== null;
    }

    /**
     * Comprueba si el usuario es administrador
     * @return bool
     */
    public function getEsAdministrador()
    {
        return $this->getRoles()->where(['nombre' => 'administrador'])->one() !== null;
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getJugadores()
    {
        return $this->hasOne(Jugadores::className(), ['id' => 'jugador_id'])->inverseOf('usuario');
    }

    /**
    * @return \yii\db\ActiveQuery
    */
    public function getNacionalidad()
    {
        return $this->hasOne(Nacionalidades::className(), ['id' => 'nacionalidad_id'])->inverseOf('usuarios');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getZonaHoraria()
    {
        return $this->hasOne(ZonasHorarias::className(), ['id' => 'zona_horaria_id'])->inverseOf('usuarios');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsuariosRoles()
    {
        return $this->hasMany(UsuariosRoles::className(), ['usuario_id' => 'id'])->inverseOf('usuario');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Roles::className(), ['id' => 'rol_id'])->viaTable('usuarios_roles', ['usuario_id' => 'id']);
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->auth_key = \Yii::$app->security->generateRandomString();

                if ($this->scenario == self::ESCENARIO_INVITAR) {
                    $this->activo     = true;
                    $this->verificado = \Yii::$app->security->generateRandomString();
                }

                if ($this->scenario == self::ESCENARIO_UNETE) {
                    $this->verificado = \Yii::$app->security->generateRandomString();
                }
            } else {
                if ($this->scenario == self::ESCENARIO_VERIFICAR) {
                    $this->verificado = null;
                    $this->password   = \Yii::$app->security->generatePasswordHash($this->password);

                    $rolMiembro = Roles::find()->where(['nombre' => 'En prueba'])->one();
                    $this->cambiarRol($rolMiembro->id);
                } else if ($this->scenario == self::ESCENARIO_PERFIL) {
                    if ($this->password && $this->password != $this->oldAttributes['password']) {
                        $this->password = \Yii::$app->security->generatePasswordHash($this->password);
                    } else {
                        $this->password = $this->oldAttributes['password'];
                    }
                }
            }
            return true;
        }
        return false;
    }
}
