<?php
namespace LeePHP\System;

use LeePHP\Base\Base;
use LeePHP\Interfaces\IPrinter;
use LeePHP\Utility\Encoder;

/**
 * 默认数据输出对象。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class DefPrinter extends Base implements IPrinter {
    /**
     * 输出数据内容到客户端。
     * 
     * @param string|array $data    指定输出的数据文本或其它对象。
     * @param int          $format  指定数据输出格式。(默认值: 0 | 文本. 可用值: 1,JSON / 2,MessagePack / 3,igbinary / 4,XML)
     * @param string       $wrapper 指定数据包装器类名。(默认值: Null)
     */
    function response($data, $format = 0, $wrapper = NULL) {
        if ($wrapper && is_callable($wrapper)) {
            $data = call_user_func($wrapper, $data);
        }

        if ($this->_format > 0) {
            echo Encoder::encode($data, $format);
        } else {
            echo $this->_data;
        }
    }
}
