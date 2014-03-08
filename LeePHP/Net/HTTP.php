<?php
namespace LeePHP\Net;

use LeePHP\Entity\RequestOption;
use LeePHP\Entity\Response;
use LeePHP\NetworkException;

/**
 * HTTP 请求工具类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class HTTP {

    /**
     * 发送 HTTP 请求。
     * 
     * @param RequestOption $option 指定请求参数对象。
     * @return Response|boolean
     */
    static function send($option) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $option->getUrl());
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $option->getHeaders());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($option->isAsyncable() && $option->getTimeoutMs() > 0) {
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $option->getTimeoutMs());
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, $option->getTimeout());
        }

        if ($option->isPostMethod()) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $option->getDataPost());
        }

        $rpo = false;

        $content = curl_exec($ch);

        $errno = curl_errno($ch);

        if (0 === $errno) {
            $rpo = new Response();

            if ($option->isInfoable()) {
                $fo = curl_getinfo($ch);

                $rpo->setContentType($fo['content_type']);
                $rpo->setHeaderSize($fo['header_size']);
                $rpo->setHttpCode($fo['http_code']);
                $rpo->setRedirectCount($fo['redirect_count']);
                $rpo->setRequestSize($fo['request_size']);
                $rpo->setTotalTime($fo['total_time']);
            }

            $rpo->setMessage($content);
        }

        curl_close($ch);

        if (0 !== $errno)
            throw new NetworkException('请求发生错误。', $errno);

        return $rpo;
    }
}
