<?php
namespace Topxia\Component\MediaParser;

use Topxia\Service\Common\ServiceKernel;

class ParserProxy
{
    public function parseItem($url)
    {
        $parsers = array('YoukuVideo', 'QQVideo', 'NeteaseOpenCourse', 'TudouVideo');

        $kernel = ServiceKernel::instance();
        $extras = array();

        if ($kernel->hasParameter('media_parser')) {
            $extras = $kernel->getParameter('media_parser');
        }

        if ($extras['item']) {
            $extrasParsers = $extras['item'];

            foreach ($extrasParsers as $extrasParser) {
                $class  = $extrasParser['class'];
                $parser = new $class();

                if (!$parser->detect($url)) {
                    continue;
                }

                return $parser->parse($url);
            }
        }

        foreach ($parsers as $parserName) {
            $class  = __NAMESPACE__."\\ItemParser\\{$parserName}ItemParser";
            $parser = new $class();

            if (!$parser->detect($url)) {
                continue;
            }

            return $parser->parse($url);
        }

        throw $this->createParserNotFoundException();
    }

    public function parseAlbum($url)
    {
        $parsers = array('YoukuVideo', 'QQVideo', 'NeteaseOpenCourse', 'SinaOpenCourse');

        if ($kernel->hasParameter('MediaParser')) {
            $extras = $kernel->getParameter('MediaParser');
        }

        if ($extras['Album']) {
            $extrasParsers = $extras['Album'];

            foreach ($extrasParsers as $extrasParser) {
                $class  = $extrasParser['class'];
                $parser = new $class();

                if (!$parser->detect($url)) {
                    continue;
                }

                return $parser->parse($url);
            }
        }

        foreach ($parsers as $parserName) {
            $class  = __NAMESPACE__."\\AlbumParser\\{$parserName}AlbumParser";
            $parser = new $class();

            if (!$parser->detect($url)) {
                continue;
            }

            return $parser->parse($url);
        }

        throw $this->createParserNotFoundException();
    }

    protected function createParserNotFoundException($message = '')
    {
        return new ParserNotFoundException($message);
    }
}
