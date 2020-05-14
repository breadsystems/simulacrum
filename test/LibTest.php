<?php

namespace Simulacrum;

use Gumlet\ImageResize;

use PHPUnit\Framework\TestCase;

class LibTest extends TestCase {
  public function testParseUri() {
    $this->assertEquals([], parse_uri(''));
  }

  public function testParseUriInvalidPath() {
    $this->assertEquals([], parse_uri('/'));
    $this->assertEquals([], parse_uri('//'));
    $this->assertEquals([], parse_uri('///'));
  }

  public function testParseUriBasic() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/cat.jpg'));
  }
 
  public function testParseUriBadOps() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/one,two/cat.jpg');
  }

  public function testParseUriCrop() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'crop',
        'params'  => [100,200],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100,200/cat.jpg'));
  }

  public function testParseUriFreecrop() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'freecrop',
        'params'  => [100,200,45,50],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100,200,45,50/cat.jpg'));
  }

  public function testParseUriCropParseErrorWidth() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/c,NONSENSE/cat.jpg');
  }

  public function testParseUriCropParseErrorHeight() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/c,100,NONSENSE/cat.jpg');
  }

  public function testParseUriCropParseErrorYOffset() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/c,100,200,50,NONSENSE/cat.jpg');
  }

  public function testParseUriCropFloats() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'freecrop',
        'params'  => [100,200,45,50.0],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100.00,200.1,45.999,50.999/cat.jpg'));
  }

  public function testParseUriCropCenter() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPCENTER],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100,200,center/cat.jpg'));
  }

  public function testParseUriCropTop() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPTOP],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100,200,top/cat.jpg'));
  }

  public function testParseUriCropBottom() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPBOTTOM],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/c,100,200,bottom/cat.jpg'));
  }

  public function testParseUriScale() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'scale',
        'params'  => [99],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/s,99/cat.jpg'));
  }

  public function testParseUriScaleParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/s,BLAH/cat.jpg');
  }

  public function testParseUriResizeToHeight() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToHeight',
        'params'  => [100],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/h,100/cat.jpg'));
  }
  
  public function testParseUriResizeToHeightAllowEnlarge() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToHeight',
        'params'  => [100,true],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/h,100,enlarge/cat.jpg'));
  }

  public function testParseUriResizeToHeightParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/h,BLAH/cat.jpg');
  }

  public function testParseUriResizeToWidth() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToWidth',
        'params'  => [100],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/w,100/cat.jpg'));
  }
  
  public function testParseUriResizeToWidthAllowEnlarge() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToWidth',
        'params'  => [100,true],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/w,100,enlarge/cat.jpg'));
  }
 
  public function testParseUriResizeToWidthParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/w,BLAH/cat.jpg');
  }

  public function testParseUriResize() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resize',
        'params'  => [100,200],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/r,100,200/cat.jpg'));
  }

  public function testParseUriResizeAllowEnlarge() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resize',
        'params'  => [100,200,true],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/r,100,200,enlarge/cat.jpg'));
  }

  public function testParseUriResizeParseErrorWidth() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/r,BLAH/cat.jpg');
  }

  public function testParseUriResizeParseErrorHeight() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/r,10,BLAH/cat.jpg');
  }

  public function testParseUriResizeToLongSide() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToLongSide',
        'params'  => [100],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/long,100/cat.jpg'));
  }

  public function testParseUriResizeToShortSide() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToShortSide',
        'params'  => [100],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/short,100/cat.jpg'));
  }

  public function testParseUriResizeToBestFit() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [[
        'op'      => 'resizeToBestFit',
        'params'  => [100,200],
      ]],
      'filename'  => 'cat.jpg',
    ], parse_uri('imagez/fit,100,200/cat.jpg'));
  }
}