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
      'extension' => 'jpg',
      'stub'      => 'cat',
      'type'      => IMAGETYPE_JPEG,
      'stat'      => [],
    ], parse_uri('imagez/cat.jpg'));
  }

  public function testParseUriBasicExtensions() {
    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [],
      'filename'  => 'cat.jpeg',
      'extension' => 'jpeg',
      'stub'      => 'cat',
      'type'      => IMAGETYPE_JPEG,
      'stat'      => [],
    ], parse_uri('imagez/cat.jpeg'));

    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [],
      'filename'  => 'cat.png',
      'extension' => 'png',
      'stub'      => 'cat',
      'type'      => IMAGETYPE_PNG,
      'stat'      => [],
    ], parse_uri('imagez/cat.png'));

    $this->assertEquals([
      'directory' => 'imagez',
      'ops'       => [],
      'filename'  => 'cat.gif',
      'extension' => 'gif',
      'stub'      => 'cat',
      'type'      => IMAGETYPE_GIF,
      'stat'      => [],
    ], parse_uri('imagez/cat.gif'));
  }

  public function testParseUriBadOps() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/one,two/cat.jpg');
  }

  public function testParseUriCrop() {
    $this->assertEquals([
      [
        'op'      => 'crop',
        'params'  => [100,200],
      ],
    ], parse_uri('imagez/c,100,200/cat.jpg')['ops']);
  }

  public function testParseUriCropLonghand() {
    $this->assertEquals([
      [
        'op'      => 'crop',
        'params'  => [100,200],
      ],
    ], parse_uri('imagez/crop,100,200/cat.jpg')['ops']);
  }

  public function testParseUriFreecrop() {
    $this->assertEquals([
      [
        'op'      => 'freecrop',
        'params'  => [100,200,45,50],
      ],
    ], parse_uri('imagez/c,100,200,45,50/cat.jpg')['ops']);
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
      [
        'op'      => 'freecrop',
        'params'  => [100,200,45,50.0],
      ],
    ], parse_uri('imagez/c,100.00,200.1,45.999,50.999/cat.jpg')['ops']);
  }

  public function testParseUriCropCenter() {
    $this->assertEquals([
      [
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPCENTER],
      ],
    ], parse_uri('imagez/c,100,200,center/cat.jpg')['ops']);
  }

  public function testParseUriCropTop() {
    $this->assertEquals([
      [
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPTOP],
      ],
    ], parse_uri('imagez/c,100,200,top/cat.jpg')['ops']);
  }

  public function testParseUriCropBottom() {
    $this->assertEquals([
      [
        'op'      => 'crop',
        'params'  => [100,200,ImageResize::CROPBOTTOM],
      ],
    ], parse_uri('imagez/c,100,200,bottom/cat.jpg')['ops']);
  }

  public function testParseUriScale() {
    $this->assertEquals([
      [
        'op'      => 'scale',
        'params'  => [99],
      ],
    ], parse_uri('imagez/s,99/cat.jpg')['ops']);
  }

  public function testParseUriScaleLonghand() {
    $this->assertEquals([
      [
        'op'      => 'scale',
        'params'  => [99],
      ],
    ], parse_uri('imagez/scale,99/cat.jpg')['ops']);
  }

  public function testParseUriScaleParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/s,BLAH/cat.jpg');
  }

  public function testParseUriResizeToHeight() {
    $this->assertEquals([
      [
        'op'      => 'resizeToHeight',
        'params'  => [100],
      ],
    ], parse_uri('imagez/h,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToHeightLonghand() {
    $this->assertEquals([
      [
        'op'      => 'resizeToHeight',
        'params'  => [100],
      ],
    ], parse_uri('imagez/resize_to_height,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToHeightAllowEnlarge() {
    $this->assertEquals([
      [
        'op'      => 'resizeToHeight',
        'params'  => [100,true],
      ],
    ], parse_uri('imagez/h,100,enlarge/cat.jpg')['ops']);
  }

  public function testParseUriResizeToHeightParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/h,BLAH/cat.jpg');
  }

  public function testParseUriResizeToWidth() {
    $this->assertEquals([
      [
        'op'      => 'resizeToWidth',
        'params'  => [100],
      ],
    ], parse_uri('imagez/w,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToWidthLonghand() {
    $this->assertEquals([
      [
        'op'      => 'resizeToWidth',
        'params'  => [100],
      ],
    ], parse_uri('imagez/resize_to_width,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToWidthAllowEnlarge() {
    $this->assertEquals([
      [
        'op'      => 'resizeToWidth',
        'params'  => [100,true],
      ],
    ], parse_uri('imagez/w,100,enlarge/cat.jpg')['ops']);
  }

  public function testParseUriResizeToWidthParseError() {
    $this->expectException(ParseError::class);
    parse_uri('imagez/w,BLAH/cat.jpg');
  }

  public function testParseUriResize() {
    $this->assertEquals([
      [
        'op'      => 'resize',
        'params'  => [100,200],
      ],
    ], parse_uri('imagez/r,100,200/cat.jpg')['ops']);
  }

  public function testParseUriResizeAllowEnlarge() {
    $this->assertEquals([
      [
        'op'      => 'resize',
        'params'  => [100,200,true],
      ],
    ], parse_uri('imagez/r,100,200,enlarge/cat.jpg')['ops']);
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
      [
        'op'      => 'resizeToLongSide',
        'params'  => [100],
      ],
    ], parse_uri('imagez/long,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToShortSide() {
    $this->assertEquals([
      [
        'op'      => 'resizeToShortSide',
        'params'  => [100],
      ],
    ], parse_uri('imagez/short,100/cat.jpg')['ops']);
  }

  public function testParseUriResizeToBestFit() {
    $this->assertEquals([
      [
        'op'      => 'resizeToBestFit',
        'params'  => [100,200],
      ],
    ], parse_uri('imagez/fit,100,200/cat.jpg')['ops']);
  }
}
