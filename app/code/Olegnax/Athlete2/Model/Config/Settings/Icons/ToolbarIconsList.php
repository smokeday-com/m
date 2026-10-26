<?php
/**
 * @author      Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2024 Olegnax (http://olegnax.com/). All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Olegnax\Athlete2\Model\Config\Settings\Icons;

class ToolbarIconsList
{
    public static function getIconsArray(){
        // icon, width, height       
        return [
            'arrow_' => ['<polygon points="10.8,7 9,8.8 9,3 7,3 7,8.8 5.2,7 4,8.2 7.8,12 8.2,12 12,8.2 "></polygon>', 16,16],
            'arrow_bold' => ['<polygon points="12.7,6.3 7,0.6 1.3,6.3 2.8,7.7 6,4.5 6,13 8,13 8,4.5 11.2,7.7 "/>', 14,14],
            'arrow_thin' => [''],
            'grid_' => ['<path d="M5,0H0v5h5V0z"/><path d="M12,0H7v5h5V0z"/><path d="M5,7H0v5h5V7z"/><path d="M12,7H7v5h5V7z"/>', 12, 12],
            'grid_bold' => ['<path d="M7,2v5H2V2H7 M8,0H1C0.4,0,0,0.4,0,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1V1C9,0.4,8.6,0,8,0L8,0z"/><path d="M18,2v5h-5V2H18 M19,0h-7c-0.6,0-1,0.4-1,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1V1C20,0.4,19.6,0,19,0L19,0z"/><path d="M7,13v5H2v-5H7 M8,11H1c-0.6,0-1,0.4-1,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1v-7C9,11.4,8.6,11,8,11L8,11z"/><path d="M18,13v5h-5v-5H18 M19,11h-7c-0.6,0-1,0.4-1,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1v-7C20,11.4,19.6,11,19,11L19,11z"/>', 20,20],
            'grid_thin' => [''],
            'list_' => ['<path d="M7,9H2v5h5V9z"/><rect x="9" y="2" width="5" height="2"/><rect x="9" y="5" width="5" height="2"/><rect x="9" y="9" width="5" height="2"/><rect x="9" y="12" width="5" height="2"/><path d="M7,2H2v5h5V2z"/>', 16,16],
            'list_bold' => ['<path d="M7,2v5H2V2H7 M8,0H1C0.4,0,0,0.4,0,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1V1C9,0.4,8.6,0,8,0L8,0z"/><path d="M7,13v5H2v-5H7 M8,11H1c-0.6,0-1,0.4-1,1v7c0,0.6,0.4,1,1,1h7c0.6,0,1-0.4,1-1v-7C9,11.4,8.6,11,8,11L8,11z"/><polygon points="20,1 11,1 11,3 20,3 20,1"/><polygon points="18,5 11,5 11,7 18,7 18,5"/><polygon points="20,12 11,12 11,14 20,14 20,12"/><polygon points="18,16 11,16 11,18 18,18 18,16"/>', 20,20],
            'list_thin' => [''],
        ];
    }
}
