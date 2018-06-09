<?php

use yii\helpers\Html;

use common\components\RedesSociales;
use common\components\RegisterThisCss;

/* @var $this yii\web\View */

$this->title = 'General - Panel de administración';

if ($configuracionAcciones['accion'] == 'd') {
    $msgAccion = 'DIRECTO';
} elseif ($configuracionAcciones['accion'] == 'p') {
    $msgAccion = 'PRÓXIMA PARTIDA';
} else {
    $msgAccion = 'NINGUNO';
}

RegisterThisCss::register($this);
?>
<div class="row">
    <div class="col-md-3 modulo">
        <div class="row titulo-cabecera centrar">
            <div class="col-md-12">
                <h3>Acciones</h3>
            </div>
        </div>
        <div class="row contenido">
            <div class="col-md-12">
                <p class="centrar">
                    Activado: <b><?= $msgAccion ?></b>
                </p>
                <?php if ($configuracionAcciones['accion'] == 'd') : ?>
                    <div class="acciones centrar">
                        <?= Html::a('Modificar directo', ['web', 'config' => 'directo'], ['class' => 'btn btn-danger btn-lg btn-extenso']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($configuracionAcciones['accion']) : ?>
                    <div class="acciones centrar">
                        <?= Html::a('Desactivar', ['accion', 'activar' => 'n'], ['class' => 'btn btn-primary btn-lg btn-extenso']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($configuracionAcciones['accion'] != 'd' && $directo) : ?>
                    <div class="acciones centrar">
                        <?= Html::a('Activar directo', ['accion', 'activar' => 'd'], ['class' => 'btn btn-danger btn-lg btn-extenso']) ?>
                    </div>
                <?php endif; ?>
                <div class="acciones centrar redes-sociales">
                    <?= RedesSociales::twitter($msgUnete['twitter'], 'Únete') ?>
                    <?= RedesSociales::whatsapp($detect, $msgUnete['whatsapp'], 'Únete') ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 modulo">
        <div class="row titulo-cabecera centrar">
            <div class="col-md-12">
                <h3>Configuración web</h3>
            </div>
        </div>
        <div class="row contenido">
            <div class="col-md-12">
                <?php if ($configuracionAcciones['accion'] != 'd') : ?>
                    <div class="acciones centrar">
                        <?= Html::a('Programar directo', ['web', 'config' => 'directo'], ['class' => 'btn btn-danger btn-lg btn-extenso']) ?>
                    </div>
                <?php endif; ?>
                <?php if ($configuracionAcciones['accion'] != 'p') : ?>
                    <div class="acciones centrar">
                        <?= Html::a('Programar próx partida', ['web', 'config' => 'proxima-partida'], ['class' => 'btn btn-warning btn-lg btn-extenso']) ?>
                    </div>
                <?php endif; ?>
                <div class="acciones centrar">
                    <?= Html::a('Contraseña admin', ['web', 'config' => 'cambiar-contrasena'], ['class' => 'btn btn-primary btn-lg btn-extenso']) ?>
                </div>
                <div class="acciones centrar">
                    <?= Html::a('Parámetros', ['administrar-cuentas'], ['class' => 'btn btn-success btn-lg btn-extenso']) ?>
                </div>
            </div>
        </div>
    </div>
</div>
