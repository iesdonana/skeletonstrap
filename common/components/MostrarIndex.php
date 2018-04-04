<?php

namespace common\components;

use yii\helpers\Html;

class MostrarIndex
{
    public static function directo($etiqueta)
    {
        ?>
        <div class="row">
            <div class="col-md-12 texto-directo">
                <h2>EN DIRECTO<i class="fa fa-circle"></i></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 twitch-video">
                <iframe
                src="http://player.twitch.tv/?channel=skeletonstraptv"
                height="720"
                width="80%"
                frameborder="0"
                scrolling="no"
                allowfullscreen="true">
                </iframe>
            </div>
        </div>
        <div class="row etiquetas">
            <div class="col-md-offset-5 col-md-2">
                <?= $etiqueta ?>
            </div>
        </div>
        <?= Partidas::mostrarPartida() ?>
        <?php
    }

    public static function proximaPartida()
    {
        ?>
        <div class="row proxima-partida">
            <div class="col-md-offset-2 col-md-8">
                <div class="row text-centered div-titulo">
                    <div class="col-md-12">
                        <h2 class="titulo-proxima-partida">PRÓXIMA PARTIDA <span class="glyphicon glyphicon-refresh btn" aria-hidden="true"></span></h2>
                    </div>
                </div>
                <div class="row text-centered div-subtitulo">
                    <div class="col-md-12 redes">
                        <h3 class="subtitulo-proxima-partida"><span class="glyphicon glyphicon-time" aria-hidden="true"></span> <hora>14:00 PM</hora> <a href="https://www.twitch.tv/skeletonstraptv" class="twitch"><i class="fa fa-twitch"></i></a></h3>
                    </div>
                </div>
                <div class="row">
                    <div class="logo logo-equipo col-md-5 col-sm-5 col-xs-6 img-centered">
                        <div class="row">
                            <div class="col-md-12">
                                <?= Html::img(CheckEnd::rutaRelativa() . 'images/logo.png', ['class' => 'img-responsive']) ?>
                            </div>
                        </div>
                        <div class="row nombres-equipos">
                            <div class="col-md-12 equipo">
                                <h3>Skeleton's Trap</h3>
                            </div>
                        </div>
                    </div>
                    <div id="versus-image" class="col-md-2 col-sm-2 img-centered hidden-xs">
                        <div class="row">
                            <div class="col-md-12">
                                <?= Html::img(CheckEnd::rutaRelativa() . 'images/versus.png', ['class' => 'img-responsive']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="logo logo-enemigo col-md-5 col-sm-5 col-xs-6 img-centered">
                        <div class="row">
                            <div class="col-md-12">
                                <?= Html::img(CheckEnd::rutaRelativa() . 'images/Logo4K1.png', ['class' => 'img-responsive']) ?>
                            </div>
                        </div>
                        <div class="row nombres-equipos">
                            <div class="col-md-12 equipo-enemigo">
                                <h3>TeamQueso</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function mejoresPartidas($coleccion)
    {
        ?>
        <iframe class="twitch-video"
            src="http://player.twitch.tv/?collection=<?= $coleccion ?>"
            height="720"
            width="80%"
            frameborder="0"
            scrolling="no"
            allowfullscreen="true">
        </iframe>
        <?php
    }
}
