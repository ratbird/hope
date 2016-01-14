<?php
/*
 * Copyright (C) 2015 <mlunzena@uos.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 */

require_once dirname(__FILE__) . '/../../bootstrap.php';
require_once 'lib/classes/Icon.class.php';
require_once 'lib/classes/Assets.class.php';

class IconClassTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        $this->memo_assets_url = Assets::url();
        Assets::set_assets_url('');
    }

    function tearDown()
    {
        Assets::set_assets_url($this->memo_assets_url);
    }

    function testAssetsImgSVG()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/blue/vote.svg" alt="vote" class="icon-role-clickable icon-shape-vote">',
            Assets::img('icons/blue/vote.svg')
        );
    }

    function testAssetsImgSVGWithAddition()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/blue/add/vote.svg" alt="vote" class="icon-role-clickable icon-shape-add/vote">',
            Assets::img('icons/blue/add/vote.svg')
        );
    }

    function testAssetsImgWithDifferentSize()
    {
        $this->assertEquals(
            '<img width="32" height="32" src="images/icons/blue/vote.svg" alt="vote" class="icon-role-clickable icon-shape-vote">',
            Assets::img('icons/blue/vote.svg', array('size' => 32))
        );
    }

    function testAssetsImgPNG()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/grey/info-circle.svg" alt="info-circle" class="icon-role-inactive icon-shape-info-circle">',
            Assets::img('icons/16/grey/info-circle.png')
        );
    }

    function testAssetsImgPNGWithAddition()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/grey/add/info-circle.svg" alt="info-circle" class="icon-role-inactive icon-shape-add/info-circle">',
            Assets::img('icons/16/grey/add/info-circle.png')
        );
    }

    function testAssetsImgWithoutExtension()
    {
        $this->assertEquals(
            '<img width="20" height="20" src="images/icons/black/staple.svg" alt="staple" class="icon-role-info icon-shape-staple">',
            Assets::img('icons/20/black/staple')
        );
    }

    function testAssetsImgWithTitleAttribute()
    {
        $this->assertEquals(
            '<img title="Mit Anhang" width="20" height="20" src="images/icons/black/staple.svg" alt="Mit Anhang" class="icon-role-info icon-shape-staple">',
            Assets::img('icons/20/black/staple', array("title" => _("Mit Anhang")))
        );
    }

    function testAssetsImgWithHspaceAttribute()
    {
        $this->assertEquals(
            '<img hspace="3" width="16" height="16" src="images/icons/blue/arr_2left.svg" alt="arr_2left" class="icon-role-clickable icon-shape-arr_2left">',
            Assets::img('icons/16/blue/arr_2left.png', array('hspace' => 3))
        );
    }

    function testAssetsImgWithClassAttribute()
    {
        $this->assertEquals(
            '<img class="text-bottom icon-role-inactive icon-shape-staple" width="20" height="20" src="images/icons/grey/staple.svg" alt="staple">',
            Assets::img('icons/20/grey/staple', array('class' => 'text-bottom'))
        );
    }

    function testAssetsImgWithClassAndTitleAttribute()
    {
        $this->assertEquals(
            '<img title="Datei hochladen" class="text-bottom icon-role-clickable icon-shape-upload" width="20" height="20" src="images/icons/blue/upload.svg" alt="Datei hochladen">',
            Assets::img('icons/20/blue/upload', array('title' => _("Datei hochladen"), 'class' => "text-bottom"))
        );
    }

    function testAssetsInput()
    {
        $this->assertEquals(
            '<input type="image" title="Datei hochladen" class="text-bottom icon-role-clickable icon-shape-upload" width="20" height="20" src="images/icons/blue/upload.svg" alt="Datei hochladen">',
            Assets::input('icons/20/blue/upload', array('title' => _("Datei hochladen"), 'class' => "text-bottom"))
        );
    }

    function testIconCreateAsImg()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/blue/vote.svg" alt="vote" class="icon-role-link icon-shape-vote">',
            Icon::create('vote', 'link')->asImg()
        );
    }

    function testIconCreateAsImgWithAddition()
    {
        $this->assertEquals(
            '<img width="16" height="16" src="images/icons/blue/add/vote.svg" alt="vote+add" class="icon-role-link icon-shape-vote+add">',
            Icon::create('vote+add', 'link')->asImg()
        );
    }

    function testIconCreateAsImgWithSize()
    {
        $this->assertEquals(
            '<img width="20" height="20" src="images/icons/blue/add/vote.svg" alt="vote+add" class="icon-role-link icon-shape-vote+add">',
            Icon::create('vote+add', 'link')->asImg(20)
        );
    }

    function testIconCreateAsImgWithTitle()
    {
        $this->assertEquals(
            '<img title="Mit Anhang" width="20" height="20" src="images/icons/blue/vote.svg" alt="Mit Anhang" class="icon-role-link icon-shape-vote">',
            Icon::create('vote', 'link', ['title' => _("Mit Anhang")])->asImg(20)
        );
    }

    function testIconCreateAsImgWithHspace()
    {
        $this->assertEquals(
            '<img hspace="3" width="16" height="16" src="images/icons/blue/arr_2left.svg" alt="arr_2left" class="icon-role-link icon-shape-arr_2left">',
            Icon::create('arr_2left', 'link')->asImg(['hspace' => 3])
        );
    }

    function testIconCreateAsImgWithClass()
    {
        $this->assertEquals(
            '<img class="text-bottom icon-role-info icon-shape-staple" width="20" height="20" src="images/icons/black/staple.svg" alt="staple">',
            Icon::create('staple', 'info')->asImg(20, ['class' => 'text-bottom'])
        );
    }

    function testIconCreateAsImgWithClassAndTitle()
    {
        $this->assertEquals(
            '<img title="Datei hochladen" class="text-bottom icon-role-new icon-shape-upload" width="20" height="20" src="images/icons/red/upload.svg" alt="Datei hochladen">',
            Icon::create('upload', 'new', ['title' => _("Datei hochladen")])
                ->asImg(20, ['class' => 'text-bottom'])
        );
    }

    function testIconCreateAsInput()
    {
        $this->assertEquals(
            '<input type="image" class="text-bottom icon-role-link icon-shape-upload" width="20" height="20" src="images/icons/blue/upload.svg" alt="upload">',
            Icon::create('upload', 'link')->asInput(20, ['class' => 'text-bottom'])
        );
    }

    function testIconIsImmutable()
    {
        $icon = Icon::create('upload', 'link', ['title' => _('a title')]);
        $copy = $icon->assoc('role', 'clickable');

        $this->assertNotSame($icon, $copy);
    }

    function testIconAssoc()
    {
        $icon = Icon::create('upload', 'link', ['title' => _('a title')]);
        $copy = $icon->assoc('role', 'clickable');

        $this->assertEquals($icon->getShape(),      $copy->getShape());
        $this->assertEquals($icon->getAttributes(), $copy->getAttributes());

        $this->assertNotEquals($icon->getRole(),    $copy->getRole());
    }
}
