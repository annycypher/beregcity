<?php
/**
 * Шапка сайта (Этап 2.2a) — разметка 1:1 по design/homepage.html.
 *
 * Классы = контракт (ПРОТОКОЛ). Меню — статикой; wp_nav_menu — шаг 2.5.
 *
 * @package Astra_Child
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#fcfaf6">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Шапка -->
<div class="hdr">
  <div class="wrap hdr-in">
    <button class="burger" id="burger" aria-label="Меню">
      <svg class="ico" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <a class="logo" href="/">
      <span class="mark"><svg width="22" height="22" viewBox="0 0 24 24" class="ico" style="stroke:#fff"><path d="M3 14c3-4 6-4 9 0s6 4 9 0"/></svg></span>
      <span><b>БерегСити</b><small>микрорайон Южный берег · Красноярск</small></span>
    </a>
    <nav class="main">
      <a href="/katalog">Каталог</a>
      <a href="/news">Новости</a>
      <a href="/afisha">Афиша</a>
      <a href="/karta">Карта</a>
      <a href="/reklama">Бизнесу</a>
    </nav>
    <div class="search">
      <input type="search" placeholder="Поиск по району…">
      <svg class="ico" style="width:19px;height:19px" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
    </div>
    <nav class="hicons">
      <a href="/afisha"><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg><span>Афиша</span></a>
      <a href="/karta"><svg class="ico" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span>Карта</span></a>
    </nav>
    <a class="btn btn-terra btn-cta" href="/dobavit">Добавить организацию</a>
  </div>
  <div class="mob-menu" id="mmenu">
    <a href="/katalog">Каталог организаций</a>
    <a href="/news">Новости</a>
    <a href="/afisha">Афиша</a>
    <a href="/karta">Карта района</a>
    <a href="/reklama">Бизнесу</a>
    <a href="/dobavit">Добавить организацию</a>
  </div>
</div>
