<?php

# Copyright (c)  2009 - Marcus Lunzenauer <mlunzena@uos.de>
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.

require_once 'lib/trails/TrailsController.php';
require_once 'lib/trails/TrailsDispatcher.php';
require_once 'lib/trails/TrailsFlash.php';
require_once 'lib/trails/TrailsInflector.php';
require_once 'lib/trails/TrailsResponse.php';
require_once 'lib/exceptions/TrailsException.php';
require_once 'mocks.php';

class FooController extends TrailsController {
  function index_action() {
  }
}

class ControllerRenderTestCase extends UnitTestCase {
  function setUp() {
    $this->setUpFS();
  }

  function tearDown() {
    stream_wrapper_unregister("var");
  }

  function setUpFS() {
    ArrayFileStream::set_filesystem(array(
      'app' => array(
        'views' => array(
          'layout.php' => '[<?= $content_for_layout ?>]',
          'foo' => array(
            'index.php'  => 'foo/index',
          )
        )
      ),
    ));
    stream_wrapper_register("var", "ArrayFileStream") or die("Failed to register protocol");
  }

  function test_should_render_default_template() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new FooController($dispatcher);
    $response = $controller->perform('index');
    $this->assertEqual($response, new TrailsResponse('foo/index'));
  }

  function test_should_render_index_action_when_there_is_no_other() {
    $controller = new FilteringController();
    $controller->expectOnce('before_filter', array('index', array()));
    $controller->setReturnValue('before_filter', FALSE);
    $response = $controller->perform('');
  }

  function test_should_throw_exception_if_action_is_missing() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new FooController($dispatcher);
    $this->expectException('TrailsUnknownAction');
    $response = $controller->perform('missing');
  }

  function test_should_redirect_if_requested() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new TrailsController($dispatcher);
    $controller->redirect('where');
    $this->assertEqual($controller->getResponse(),
                       new TrailsResponse('',
                                           array('Location' => 'trails_uri/where'),
                                           302));
  }

  function test_should_redirect_server_relative_paths() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new TrailsController($dispatcher);
    $controller->redirect('/where');
    $this->assertEqual($controller->getResponse(),
                       new TrailsResponse('',
                                           array('Location' => '/where'),
                                           302));
  }

  function test_should_redirect_to_absolute_urls() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new TrailsController($dispatcher);
    $controller->redirect('http://example.com');
    $this->assertEqual($controller->getResponse(),
                       new TrailsResponse('',
                                           array('Location' => 'http://example.com'),
                                           302));
  }

  function test_should_throw_exception_if_rendering_more_than_once() {
    $controller = new TrailsController(NULL);
    $this->expectException('TrailsDoubleRenderError');
    $controller->render_nothing();
    $controller->render_nothing();
  }

  function test_should_throw_exception_if_rendering_and_redirecting() {
    $controller = new TrailsController(NULL);
    $this->expectException('TrailsDoubleRenderError');
    $controller->render_nothing();
    $controller->redirect('');
  }

  function test_should_render_template_undecorated_with_implicit_layout() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new TrailsController($dispatcher);
    $controller->set_layout('layout');
    $controller->render_template('foo/index');
    $this->assertEqual($controller->getResponse(), new TrailsResponse('foo/index'));
  }

  function test_should_render_template_decorated_with_explicit_layout() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new TrailsController($dispatcher);
    $controller->render_template('foo/index', 'layout');
    $this->assertEqual($controller->getResponse(), new TrailsResponse('[foo/index]'));
  }

  function test_should_render_action() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new FooController($dispatcher);
    $controller->render_action('index');
    $this->assertEqual($controller->getResponse(), new TrailsResponse('foo/index'));
  }

  function test_should_render_action_with_layout() {
    $dispatcher = new TrailsDispatcher('var://app', 'trails_uri', 'default');
    $controller = new FooController($dispatcher);
    $controller->set_layout('layout');
    $controller->render_action('index');
    $this->assertEqual($controller->getResponse(), new TrailsResponse('[foo/index]'));
  }
}

