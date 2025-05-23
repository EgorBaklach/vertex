<?php namespace App\Helpers\String;

class Shower
{
    public static function printPre($var, $title = false): string
    {
        $html = '';

        $arBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $bIsFirstCall = $arBacktrace[1]['function'] != __FUNCTION__;

        if($bIsFirstCall)
        {
            $html .= '<div class="printPre">';
            $html .= "
            <style>
                .printPre {position:relative;max-width:100%}
                .printPre * {cursor:default;font-family: Arial, Helvetica, sans-serif;font-size:10px;color:#000;}
                .printPre .headlink{}
                .printPre .headlink:before{content: '+ '}
                .printPre .headlink .showlink{cursor:pointer;text-decoration:none;color:#33567f;}
                .printPre .headlink ul{display:none;list-style:none;padding: 8px 0 8px 30px;margin:0}
                .printPre .headlink.active > ul{display:block}
                .printPre .headlink.active:before{content: '- '}
                .printPre .key{color:#999}
                .printPre .type{color:#999}
                .printPre .type.string{color:#74ae81}
                .printPre .type.integer{color:#668eae}
                .printPre .type.boolean{color:#ae5750}
            </style>";
        }

        if(is_array($var) || is_object($var))
        {
            if($bIsFirstCall)
            {
                $html .= '<p class="firstString">'.(strlen($title) ? $title : $arBacktrace[0]["file"].', on line '.$arBacktrace[0]["line"]).': </p>';
            }

            $html .= '<span class="headlink">';
            $html .= '<span class="showlink" onclick="const parent=this.parentElement,pClass=parent.className,arMatches=pClass.match(/active/g)||[];parent.className=arMatches.length?pClass.replace(/ active/g,\'\'):pClass+\' active\';">'.(is_array($var) ? 'array ' : 'object '.get_class($var)).' ('.count((array)$var).')'.'</span> (';
            $html .= '<ul>';

            foreach ($var as $key => $value)
            {
                $html .= '<li><span class="key">['.$key.'] => </span>'.self::printPre($value)."</li>";
            }

            $html .= "</ul> )</span>";

        }
        else
        {
            $html .= '<span>';

            if ($bIsFirstCall)
            {
                $html .= $title !== false ? $title.": " : $arBacktrace[0]["file"].', line '.$arBacktrace[0]["line"].": ";
            }

            $type = gettype($var);

            switch($type)
            {
                case 'boolean':
                    $html .= $var ? 'true' : 'false';
                    break;
                case 'string':
                    $html .= htmlspecialchars($var);
                    break;
                default:
                    $html .= $var;
                    break;
            }

            $html .= '</span> <span class="type '.$type.'">('.($type == 'string' ? 'string '.strlen($var) : $type).')</span>';
        }

        if ($bIsFirstCall)
        {
            $html .= '</div>';
        }

        return $html;
    }
}
