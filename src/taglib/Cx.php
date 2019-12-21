<?php
namespace Nezimi\taglib;

use Exception;
use Nezimi\Taglib;

class Cx extends TagLib
{

    /**
     * 
     */
    public $tags = [
        'if' => ['expression'=>true],
        'elseif' => ['attr'=>'', 'close'=> 0, 'expression'=>true],
        'else' => ['attr'=>'', 'close'=> 0, 'expression'=>true],
        'foreach' => ['expression'=>true],
    ];

    public function tagIf(array $tag): array
    {
        $condition = $tag['expression'] ?? '';
        $condition = $this->parseCondition($condition);
        $begin = '<?php if('. $condition .'): ?>';
        $end   = '<?php endif; ?>';
        return [$begin, $end];
    }

    public function tagElse(array $tag): string
    {
        return '<?php else: ?>';
    }

    public function tagElseif(array $tag): string
    {
        $condition = $tag['expression'] ?? '';
        $condition = $this->parseCondition($condition);
        return '<?php elseif('. $condition .'): ?>';
    }

    public function tagForeach(array $tag): array
    {
        $condition = $tag['expression'] ?? '';
        $condition = $this->parseCondition($condition);
        $begin = '<?php foreach('. $condition .'): ?>';
        $end   = '<?php endforeach; ?>';
        return [$begin, $end];
    }
}