<?php
/*
Copyright (c) 2020 Ryan Nolan

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Box\Mod\Scss\Api;

require_once BB_PATH_MODS . '/Scss/third-party/scssphp/scss.inc.php';

use ScssPhp\ScssPhp\Compiler;

class Guest extends \Api_Abstract
{
    public function asset($file = 'style.scss', $media = 'screen', $forceCompile = false): string
    {
        $service            = $this->getService();
        $themeService       = $service->getServiceTheme();
        $urlService         = $service->getServiceUrl();
        $themecode          = $themeService->getCurrentClientAreaThemeCode();

        $assetDir   = '/bb-themes/' . $themecode . '/assets';
        $scssDir    = $assetDir . '/scss';
        $cssDir     = $assetDir . '/css';
        $vendorDir  = $assetDir . '/vendor';
        $imgDir     = $assetDir . '/img';

        if (!file_exists(BB_PATH_ROOT . $scssDir))
        {
            if (!mkdir(BB_PATH_ROOT . $scssDir, 0777, true))
            {
                error_log(BB_PATH_ROOT . $scssDir .' - Failed to create folder.');
                return '<!-- BB_PATH_ROOT '. $scssDir .' - Failed to create folder -->';
            }
        }

        if (!file_exists(BB_PATH_ROOT . $cssDir))
        {
            if (!mkdir(BB_PATH_ROOT . $cssDir, 0777, true))
            {
                error_log(BB_PATH_ROOT . $cssDir .' - Failed to create folder.');
                return '<!-- BB_PATH_ROOT '. $cssDir .' - Failed to create folder -->';
            }
        }

        if (!file_exists(BB_PATH_ROOT . $vendorDir))
        {
            if (!mkdir(BB_PATH_ROOT . $vendorDir, 0777, true))
            {
                error_log(BB_PATH_ROOT . $vendorDir .' - Failed to create folder.');
                return '<!-- BB_PATH_ROOT '. $vendorDir .' - Failed to create folder -->';
            }
        }

        // Getting a compiler instance
        $scssphp  = new Compiler();

        $scssphp->setVariables(array(
            'assets-path'           => $assetDir,
            'scss-assets-path'      => $scssDir,
            'css-assets-path'       => $cssDir,
            'vendor-assets-path'    => $vendorDir,
            'img-assets-path'       => $imgDir
        ));

        if (APPLICATION_ENV == 'production')
        {
            $scssphp->setFormatter('ScssPhp\ScssPhp\Formatter\Crunched');
        }else{
            $scssphp->setFormatter('ScssPhp\ScssPhp\Formatter\Expanded');
        }

        $scssphp->setImportPaths(BB_PATH_ROOT . $scssDir);

        $scssphp->addImportPath(function($path) use ($vendorDir) {
            if (!file_exists(BB_PATH_ROOT . $vendorDir)) return;
            return BB_PATH_ROOT . $vendorDir;
        });

        $filename = pathinfo($file, PATHINFO_FILENAME);

        $in     = BB_PATH_ROOT . $scssDir . '/' . $file;
        $out    = BB_PATH_ROOT . $cssDir . '/' . $filename . '.css';

        if (!file_exists($in))
        {
            error_log('Failed to find file: ' . $file . '.');
            return '<!-- Failed to find file: ' . $file . '. -->';
        }

        // Only compile if scss file has been modified or force compile has been asked for
        if (!file_exists($out) || filemtime($in) > filemtime($out) || $forceCompile == true)
        {
            $css = $scssphp->compile(file_get_contents($in));
            file_put_contents($out, $css);
        }

        $url = $urlService->get(ltrim($cssDir . '/' . $filename . '.css', '/'));

        return '<link rel="stylesheet" type="text/css" href="' . $url . '" media="' . $media . '" />';
    }
}
