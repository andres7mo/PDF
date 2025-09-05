<?php

/**
 * File: /model/e2pdf-signature.php
 *
 * @package  E2Pdf
 * @license  GPLv3
 * @link     https://e2pdf.com
 */
if (!defined('ABSPATH')) {
    die('Access denied.');
}

class Model_E2pdf_Signature extends Model_E2pdf_Model {

    public function ttf_signature($value, $options) {

        $response = '';

        if ($value && trim($value) != '' && extension_loaded('gd') && function_exists('imagettftext')) {

            $box = imagettfbbox($options['fontSize'], 0, $options['font'], $value);

            $min_x = min(array($box[0], $box[2], $box[4], $box[6]));
            $max_x = max(array($box[0], $box[2], $box[4], $box[6]));
            $min_y = min(array($box[1], $box[3], $box[5], $box[7]));
            $max_y = max(array($box[1], $box[3], $box[5], $box[7]));

            $box = array(
                'x' => abs($min_x),
                'y' => abs($min_y),
                'width' => $max_x - $min_x,
                'height' => $max_y - $min_y,
            );

            $box = $this->ttf_box_fix($value, $box, $options['fontSize'], $options['font']);

            if ($box['width'] > 0 && $box['height'] > 0) {
                $img = imagecreatetruecolor($box['width'], $box['height']);
                if ($options['bgColour'] == 'transparent') {
                    imagealphablending($img, false);
                    imagesavealpha($img, true);
                    $bg = imagecolorallocatealpha($img, 0, 0, 0, 127);
                } else {
                    $bg = imagecolorallocate($img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2]);
                }

                $pen = imagecolorallocate($img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2]);
                imagefill($img, 0, 0, $bg);
                imagettftext($img, $options['fontSize'], 0, $box['x'], $box['y'], $pen, $options['font'], $value);
                ob_start();
                imagepng($img);
                $tmp_image = ob_get_contents();
                ob_end_clean();
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
                $response = base64_encode($tmp_image);
            }
        }

        return $response;
    }

    /*
     * https://github.com/unlocomqx/text-measure
     */

    public function ttf_box_fix($value, $box, $size, $font) {

        $img = $this->ttf_tmp($value, $box, $size, $font);

        $top_line = true;
        while ($top_line) {
            $found = false;
            $y = 1;
            for ($x = 0; $x < $box['width']; $x++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $top_line = false;
                break;
            } else {
                $box['y']++;
                $box['height']++;
                imagedestroy($img);
                $img = $this->ttf_tmp($value, $box, $size, $font);
            }
        }

        $bottom_line = true;
        while ($bottom_line) {
            $found = false;
            $y = $box['height'] - 1;
            for ($x = 0; $x < $box['width']; $x++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $bottom_line = false;
                break;
            } else {
                $box['height']++;
                imagedestroy($img);
                $img = $this->ttf_tmp($value, $box, $size, $font);
            }
        }

        $left_line = true;
        while ($left_line) {
            $found = false;
            $x = 1;
            for ($y = 0; $y < $box['height']; $y++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $left_line = false;
                break;
            } else {
                $box['x']++;
                $box['width']++;
                imagedestroy($img);
                $img = $this->ttf_tmp($value, $box, $size, $font);
            }
        }

        $right_line = true;
        while ($right_line) {
            $found = false;
            $x = $box['width'] - 1;
            for ($y = 0; $y < $box['height']; $y++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $right_line = false;
                break;
            } else {
                $box['width']++;
                imagedestroy($img);
                $img = $this->ttf_tmp($value, $box, $size, $font);
            }
        }

        $found = false;
        for ($y = 0; $y < $box['height']; $y++) {
            for ($x = 0; $x < $box['width']; $x++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $box['y']--;
                $box['height']--;
            } else {
                break;
            }
        }

        $found = false;
        for ($y = $box['height'] - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $box['width']; $x++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $box['height']--;
            } else {
                break;
            }
        }

        $found = false;
        for ($x = 0; $x < $box['width']; $x++) {
            for ($y = 0; $y < $box['height']; $y++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $box['x']--;
                $box['width']--;
            } else {
                break;
            }
        }

        $found = false;
        for ($x = $box['width'] - 1; $x >= 0; $x--) {
            for ($y = 0; $y < $box['height']; $y++) {
                $color = imagecolorat($img, $x, $y);
                if ($color) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $box['width']--;
            } else {
                break;
            }
        }
        return $box;
    }

    public function ttf_tmp($value, $box, $size, $font) {
        $img = imagecreatetruecolor($box['width'], $box['height']);
        $black = imagecolorallocate($img, 0, 0, 0);
        $red = imagecolorallocate($img, 255, 0, 0);
        imagefill($img, 0, 0, $black);
        imagettftext($img, $size, 0, (int) $box['x'], $box['y'], $red, $font, $value);
        imagecolordeallocate($img, $black);
        imagecolordeallocate($img, $red);
        return $img;
    }

    public function j_signature($value, $options = array()) {

        $response = '';

        $value = str_replace('image/jsignature;base30,', '', $value);
        $a = $this->helper->load('jSignature')->Base64ToNative($value);

        $width = 0;
        $height = 0;

        foreach ($a as $line) {
            if (max($line ['x']) > $width) {
                $width = max($line ['x']);
            }
            if (max($line ['y']) > $height) {
                $height = max($line ['y']);
            }
        }

        $img = imagecreatetruecolor($width, $height);

        if ($options['bgColour'] == 'transparent') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $bg = imagecolorallocatealpha($img, 0, 0, 0, 127);
        } else {
            $bg = imagecolorallocate($img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2]);
        }

        imagefill($img, 0, 0, $bg);
        imagesetthickness($img, 5);

        $pen = imagecolorallocate($img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2]);

        // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed, Squiz.PHP.DisallowSizeFunctionsInLoops.Found
        for ($i = 0; $i < count($a); $i++) {
            // phpcs:ignore Generic.CodeAnalysis.ForLoopWithTestFunctionCall.NotAllowed, Squiz.PHP.DisallowSizeFunctionsInLoops.Found
            for ($j = 0; $j < count($a[$i]['x']); $j++) {
                if (!isset($a[$i]['x'][$j])) {
                    break;
                }
                if (!isset($a[$i]['x'][$j + 1])) {
                    imagesetpixel($img, $a[$i]['x'][$j], $a[$i]['y'][$j], $pen);
                } else {
                    imageline($img, $a[$i]['x'][$j], $a[$i]['y'][$j], $a[$i]['x'][$j + 1], $a[$i]['y'][$j + 1], $pen);
                }
            }
        }

        $tmp_image = $this->j_signature_trimbox($img);
        imagedestroy($img);

        if ($tmp_image) {
            // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
            $response = base64_encode($tmp_image);
        }

        return $response;
    }

    public function j_signature_trimbox($img = false) {
        if (!$img) {
            return '';
        }

        if (is_string($img)) {
            $img = imagecreatefrompng($img);
        }

        $width = imagesx($img);
        $height = imagesy($img);

        $top = 0;
        $bottom = 0;
        $left = 0;
        $right = 0;

        $bgcolor = imagecolorat($img, $top, $left);
        for (; $top < $height; ++$top) {
            for ($x = 0; $x < $width; ++$x) {
                if (imagecolorat($img, $x, $top) != $bgcolor) {
                    break 2;
                }
            }
        }

        for (; $bottom < $height; ++$bottom) {
            for ($x = 0; $x < $width; ++$x) {
                if (imagecolorat($img, $x, $height - $bottom - 1) != $bgcolor) {
                    break 2;
                }
            }
        }

        for (; $left < $width; ++$left) {
            for ($y = 0; $y < $height; ++$y) {
                if (imagecolorat($img, $left, $y) != $bgcolor) {
                    break 2;
                }
            }
        }

        for (; $right < $width; ++$right) {
            for ($y = 0; $y < $height; ++$y) {
                if (imagecolorat($img, $width - $right - 1, $y) != $bgcolor) {
                    break 2;
                }
            }
        }

        $newimg = imagecreate($width - ($left + $right), $height - ($top + $bottom));
        imagecopy($newimg, $img, 0, 0, $left, $top, imagesx($newimg), imagesy($newimg));

        ob_start();
        imagepng($newimg);
        $imagedata = ob_get_contents();
        ob_end_clean();

        return $imagedata;
    }
}
