<?php

namespace common\models;

use yii\base\Model;

/**
 * Clase de la que heredaran aquellos modelos que necesiten realizar una búsqueda
 * de datos de CR.
 */
abstract class ClashRoyaleCache extends \yii\db\ActiveRecord
{
    /**
     * Error que se muestra cuando hay un error con la conexion a la api desde
     * el componente ClashRoyaleAPI
     * @var string
     */
    const ERROR_API = 'Ha ocurrido un error insperado con la conexión a los datos en tiempo real. Por favor, inténtelo de nuevo más tarde.';

    /**
     * Error que se muestra cuando no se ha podido guardar los modelos recopilados en la BD
     * @var string
     */
    const ERROR_GUARDAR = 'No se ha podido realizar la búsqueda de algunos datos correctamente.';

    /**
     * Implementar de manera que devuelva un array con las claves que se quieren buscar
     * en el modelo de datos json de la api.
     * Para poder buscar recursivamente entre objetos json hay que hacer separaciones con
     * el delimitador ".". Ejemplo: stats.level
     * @var array
     */
    abstract public static function clavesCache();

    /**
     * Debe devolver las claves de los labels del modelo.
     * @var array
     */
    abstract public static function clavesLabelsStatic();

    /**
     * Debe devolver la combinacion del array que devuelve el método clavesLabelsStatic() y
     * los nombres de los valores de los labels del modelo.
     * @var array
     */
    abstract public static function attributeLabelsStatic();

    /**
     * Hace una búsqueda de datos en la BD local o a través del componente (ClashRoyaleAPI o ClashRoyaleData)
     * dependiendo si ya existen previamente los datos o no.
     * Si no existen los datos los guarda en la BD local en la tabla correspondiente.
     * Se puede forzar la búsqueda con el último parámetro.
     * En el caso de que no se pueda realizar la búsqueda a través de ClashRoyaleAPI,
     * pasados unos segundos y de forma automática, se realizará de la forma alternativa
     * a través del componente ClashRoyaleData.
     * @param  string  $metodo           Método para la búsqueda. Debe de existir en el componente ClashRoyaleAPI y ClashRoyaleData.
     * @param  array   $busquedaAPI      Array con la clave como primer elemento y de valor otro array con los valores.
     * @param  bool    $bQuery           TRUE -> Devuelve la query con la que se hace la consulta a la base de datos.
     *                                   FALSE -> Por defecto, no tiene efecto.
     * @return Model|string              Devuelve uno o más modelos distintos dependiendo de los datos que se busquen. O un error si algo falla.
     */
    public static function findAPI(string $metodo, array $busquedaAPI, bool $bQuery = false)
    {
        $clave    = array_keys($busquedaAPI)[0];
        $aValores = $busquedaAPI[$clave];

        $nBusquedas = count($aValores);

        $query = self::find()
                     ->where([$clave => $aValores[0]]);

        for ($i = 1; $i < $nBusquedas; $i++) {
            $query = $query->orWhere([$clave => $aValores[$i]]);
        }

        if ($bQuery) {
            return $query;
        }

        $models = $query->all();
        $nModels = count($models);

        $aClavesModelos = [];

        for ($i = 0; $i < $nModels; $i++) {
            $aClavesModelos[$i] = $models[$i]->{$clave};
        }

        for ($i = 0; $i < $nModels; $i++) {
            $aClavesModelos[$i] = $models[$i]->{$clave};
        }

        $aValoresConsultaAPI = [];

        $aValoresSinBD = array_values(array_diff($aValores, $aClavesModelos));
        $nValoresSinBD = count($aValoresSinBD);

        $aValoresEnBD = array_values(array_diff($aValores, $aValoresSinBD));
        $nValoresEnBD = count($aValoresEnBD);

        // Comprobar que API escoger con la version de la CR API
        $api = null;
        $version = null;

        $loadAPI = function () use (&$api, &$version) {
            $api     = \Yii::$app->crapi;
            $version = $api->version();

            if ($api->version() == null) {
                $api = new \common\components\ClashRoyaleData();
                $version = $api->version();
            }
        };

        $loadAPI();

        ConfigTiempoActualizado::clearRegistros();

        for ($i = 0; $i < $nValoresSinBD; $i++) {
            $subRutaWeb = $api->getRutasDatos()[$metodo] . '/' . $aValoresSinBD[$i];

            if ($api->actualizarDatos($subRutaWeb)) {
                $aValoresConsultaAPI[] = $aValoresSinBD[$i];
            }
        }

        for ($i = 0; $i < $nValoresEnBD; $i++) {
            $subRutaWeb = $api->getRutasDatos()[$metodo] . '/' . $aValoresEnBD[$i];

            if ($api->actualizarDatos($subRutaWeb)) {
                $aValoresConsultaAPI[] = $aValoresEnBD[$i];
            }
        }

        $nValoresConsultaAPI = count($aValoresConsultaAPI);

        if (!empty($aValoresConsultaAPI)) {
            $atributosJSON = $api->{$metodo}($aValoresConsultaAPI);

            $posibleErrorAPI = isset($atributosJSON->error) ? $atributosJSON->error : false;

            if ($atributosJSON == null || empty($atributosJSON) || $posibleErrorAPI) {
                //return static::ERROR_API;
                return $models;
            }

            if (!is_array($atributosJSON)) {
                $atributosJSON = [$atributosJSON];
            }

            $atributosLabelsStatic = static::attributeLabelsStatic();
            $clavesCache = static::clavesCache();

            for ($m = 0; $m < $nValoresConsultaAPI; $m++) {
                foreach ($clavesCache as $key => $value) {
                    $claveTemp = $key;
                    $separacionClave = explode('.', $claveTemp);

                    $nObjetos = count($separacionClave);

                    $claveValorAtributo = $separacionClave;

                    $claveAtributo = $value;
                    $valorAtributo = isset($atributosJSON[$m]->{$separacionClave[0]}) ? $atributosJSON[$m]->{$separacionClave[0]} : null;

                    if ($valorAtributo !== null) {
                        if ($nObjetos > 1) {
                            for ($o = 1; $o < $nObjetos; $o++) {
                                $valorAtributo = $valorAtributo->{$separacionClave[$o]};
                            }
                        }

                        $atributos[$m][$claveAtributo] = $valorAtributo;
                    }
                }

                // Crear uno nuevo
                $esNuevo = false;

                $nombreClase = self::className();
                $modelTemp   = new $nombreClase($atributos[$m]);

                if (!$modelTemp->validate()) {
                    // Actualizar uno ya existente
                    $modelTemp = $nombreClase::find()
                                             ->where([$clave => $aValoresConsultaAPI[$m]])
                                             ->one();

                    foreach ($atributos[$m] as $key => $value) {
                        $modelTemp[$key] = $value;
                    }
                } else {
                    $models[] = $modelTemp;
                    $esNuevo = true;
                }

                if (!$modelTemp->save()) {
                    return static::ERROR_GUARDAR;
                }

                // Refresh datos del modelo actualizado
                if (!$esNuevo) {
                    for ($indiceModel = 0; $indiceModel < $nModels; $indiceModel++) {
                        if ($models[$indiceModel]->tag == $modelTemp->tag) {
                            $models[$indiceModel]->refresh();
                            $indiceModel = $nModels;
                        }
                    }
                }
            }
        }

        return $models;
    }
}