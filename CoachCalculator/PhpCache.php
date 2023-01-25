<?php

class PhpCache
{
    private $cacheFolder = __DIR__ . "/cache";
    private $cacheExpires = 3600;

    public function DoesCacheKeyExist($key)
    {
        $cachefile = $this->cacheFolder . '/' . $key;
        if (file_exists($cachefile) == false) {
            return false;
        }
        $creationTime = @filemtime($cachefile);
        return ((time() - $this->cacheExpires) < $creationTime);
    }

    public function ReadFromCache($key)
    {
        $cachefile = $this->cacheFolder . '/' . $key;
        return file_get_contents($cachefile);
    }

    public function WriteToCache($key, $value)
    {
        $cachefile = $this->cacheFolder . '/' . $key;
        $fp = fopen($cachefile, 'w');
        fwrite($fp, $value);
        fclose($fp);
    }
}
