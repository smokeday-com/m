<?php /**/

namespace Olegnax\MegaMenu\Model\Attribute;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class MenuIcons extends AbstractSource
{
	/**
	 * Retrieve option array
	 *
	 * @return array
	 */
	public function getOptionArray()
	{
		$_options = [];
		foreach ($this->getAllOptions() as $option) {
			$_options[$option['value']] = $option['label'];
		}
		return $_options;
	}
    /**
     * @var array
     */
    private $iconArray;

	/**
	 * Retrieve all options array
	 *
	 * @return array
	 */
	public function getAllOptions()
	{
		if ($this->_options === null) {
			$this->_options = [
				['label' => __('None'), 'value' => '', 'icon' => ''],
				['label' => __('Washing Machine'), 'value' => 'washing-machine', 'icon' => '<path d="M18,5v15H6V5H18 M19,3H5C4.4,3,4,3.4,4,4v17c0,0.6,0.4,1,1,1h14c0.6,0,1-0.4,1-1V4C20,3.4,19.6,3,19,3L19,3z"/>
				<path d="M12,11c1.7,0,3,1.3,3,3s-1.3,3-3,3s-3-1.3-3-3S10.3,11,12,11 M12,9c-2.8,0-5,2.2-5,5s2.2,5,5,5s5-2.2,5-5S14.8,9,12,9L12,9z"/>
				<polygon points="9,6 7,6 7,8 9,8 "/>
				<polygon points="12,6 10,6 10,8 12,8 "/>'],
				['label' => __('Tv'), 'value' => 'tv', 'icon' => '<path d="M2 4.00087C2 3.44811 2.45531 3 2.9918 3H21.0082C21.556 3 22 3.44463 22 4.00087V17.9991C22 18.5519 21.5447 19 21.0082 19H2.9918C2.44405 19 2 18.5554 2 17.9991V4.00087ZM4 5V17H20V5H4ZM5 20H19V22H5V20Z"></path>'],
				['label' => __('Smartphone'), 'value' => 'smartphone', 'icon' => '<path d="M7 4V20H17V4H7ZM6 2H18C18.5523 2 19 2.44772 19 3V21C19 21.5523 18.5523 22 18 22H6C5.44772 22 5 21.5523 5 21V3C5 2.44772 5.44772 2 6 2ZM12 17C12.5523 17 13 17.4477 13 18C13 18.5523 12.5523 19 12 19C11.4477 19 11 18.5523 11 18C11 17.4477 11.4477 17 12 17Z"></path>'],
				['label' => __('Watches'), 'value' => 'watches', 'icon' => '<path d="M17,5V3c0-1.1-0.9-2-2-2H9C7.9,1,7,1.9,7,3v2C5.9,5,5,5.9,5,7v10c0,1.1,0.9,2,2,2v2c0,1.1,0.9,2,2,2h6c1.1,0,2-0.9,2-2v-2 c1.1,0,2-0.9,2-2V7C19,5.9,18.1,5,17,5z M9,3h6v2H9V3z M15,21H9v-2h6V21z M17,17H7V7h10V17z"/>
				<polygon points="15,11 13,11 13,9 11,9 11,11 11,12 11,13 15,13 "/>'],
				['label' => __('Scooter'), 'value' => 'scooter', 'icon' => '<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M19.8,19.6
				c0,0.2,0,0.4,0.1,0.6c0.1,0.2,0.2,0.3,0.3,0.5s0.3,0.2,0.5,0.3c0.2,0.1,0.4,0.1,0.6,0.1s0.4,0,0.6-0.1c0.2-0.1,0.3-0.2,0.5-0.3
				s0.2-0.3,0.3-0.5s0.1-0.4,0.1-0.6c0-0.2,0-0.4-0.1-0.6s-0.2-0.3-0.3-0.5s-0.3-0.2-0.5-0.3c-0.2-0.1-0.4-0.1-0.6-0.1s-0.4,0-0.6,0.1
				c-0.2,0.1-0.3,0.2-0.5,0.3S20,18.9,19.9,19C19.9,19.2,19.8,19.4,19.8,19.6z"/>
			<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M1.2,19.6
				c0,0.4,0.2,0.8,0.4,1s0.6,0.4,1,0.4s0.8-0.2,1-0.4s0.4-0.6,0.4-1c0-0.4-0.2-0.8-0.4-1s-0.6-0.4-1-0.4s-0.8,0.2-1,0.4
				S1.2,19.2,1.2,19.6z"/>
			<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M0.8,16.2
				c0.6-0.3,1.2-0.5,1.8-0.5c0.6,0,1.3,0.1,1.9,0.4c0.6,0.3,1.1,0.7,1.5,1.2c0.4,0.5,0.6,1.1,0.7,1.8h10.8c0.1-0.6,0.3-1.3,0.7-1.8
				c0.4-0.5,0.9-1,1.5-1.2c0.6-0.3,1.2-0.4,1.9-0.4c0.6,0,1.3,0.2,1.8,0.5"/>
			<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M19.3,16.2L17.8,2.9"/>
			<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M15.4,2.9h4.9"/>
			<path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" d="M8.8,16.2h6.3"/>'],
				['label' => __('Printer'), 'value' => 'printer', 'icon' => '<path d="M17 2C17.5523 2 18 2.44772 18 3V7H21C21.5523 7 22 7.44772 22 8V18C22 18.5523 21.5523 19 21 19H18V21C18 21.5523 17.5523 22 17 22H7C6.44772 22 6 21.5523 6 21V19H3C2.44772 19 2 18.5523 2 18V8C2 7.44772 2.44772 7 3 7H6V3C6 2.44772 6.44772 2 7 2H17ZM16 17H8V20H16V17ZM20 9H4V17H6V16C6 15.4477 6.44772 15 7 15H17C17.5523 15 18 15.4477 18 16V17H20V9ZM8 10V12H5V10H8ZM16 4H8V7H16V4Z"></path>'],
				['label' => __('Laptop'), 'value' => 'laptop', 'icon' => '<path d="M4 5V16H20V5H4ZM2 4.00748C2 3.45107 2.45531 3 2.9918 3H21.0082C21.556 3 22 3.44892 22 4.00748V18H2V4.00748ZM1 19H23V21H1V19Z"></path>'],
				['label' => __('Headphone'), 'value' => 'headphone', 'icon' => '<path d="M12 4C7.58172 4 4 7.58172 4 12H7C8.10457 12 9 12.8954 9 14V19C9 20.1046 8.10457 21 7 21H4C2.89543 21 2 20.1046 2 19V12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12V19C22 20.1046 21.1046 21 20 21H17C15.8954 21 15 20.1046 15 19V14C15 12.8954 15.8954 12 17 12H20C20 7.58172 16.4183 4 12 4ZM4 14V19H7V14H4ZM17 14V19H20V14H17Z"></path>'],
				['label' => __('Gamepad'), 'value' => 'gamepad', 'icon' => '<path d="M17 4C20.3137 4 23 6.68629 23 10V14C23 17.3137 20.3137 20 17 20H7C3.68629 20 1 17.3137 1 14V10C1 6.68629 3.68629 4 7 4H17ZM17 6H7C4.8578 6 3.10892 7.68397 3.0049 9.80036L3 10V14C3 16.1422 4.68397 17.8911 6.80036 17.9951L7 18H17C19.1422 18 20.8911 16.316 20.9951 14.1996L21 14V10C21 7.8578 19.316 6.10892 17.1996 6.0049L17 6ZM10 9V11H12V13H9.999L10 15H8L7.999 13H6V11H8V9H10ZM18 13V15H16V13H18ZM16 9V11H14V9H16Z"></path>'],
				['label' => __('Fridge'), 'value' => 'fridge', 'icon' => '<path d="M18.998 1C19.5503 1 19.998 1.44772 19.998 2V22C19.998 22.5523 19.5503 23 18.998 23H4.99805C4.44576 23 3.99805 22.5523 3.99805 22V2C3.99805 1.44772 4.44576 1 4.99805 1H18.998ZM17.998 12H5.99805V21H17.998V12ZM9.99805 14V18H7.99805V14H9.99805ZM17.998 3H5.99805V10H17.998V3ZM9.99805 5V8H7.99805V5H9.99805Z"></path>'],
				['label' => __('Computer'), 'value' => 'computer', 'icon' => '<path d="M4 16H20V5H4V16ZM13 18V20H17V22H7V20H11V18H2.9918C2.44405 18 2 17.5511 2 16.9925V4.00748C2 3.45107 2.45531 3 2.9918 3H21.0082C21.556 3 22 3.44892 22 4.00748V16.9925C22 17.5489 21.5447 18 21.0082 18H13Z"></path>'],
				['label' => __('Camera'), 'value' => 'camera', 'icon' => '<path d="M9.82843 5L7.82843 7H4V19H20V7H16.1716L14.1716 5H9.82843ZM9 3H15L17 5H21C21.5523 5 22 5.44772 22 6V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V6C2 5.44772 2.44772 5 3 5H7L9 3ZM12 18C8.96243 18 6.5 15.5376 6.5 12.5C6.5 9.46243 8.96243 7 12 7C15.0376 7 17.5 9.46243 17.5 12.5C17.5 15.5376 15.0376 18 12 18ZM12 16C13.933 16 15.5 14.433 15.5 12.5C15.5 10.567 13.933 9 12 9C10.067 9 8.5 10.567 8.5 12.5C8.5 14.433 10.067 16 12 16Z"></path>'],
				['label' => __('Camera2'), 'value' => 'camera2', 'icon' => '<path d="M2 6.00087C2 5.44811 2.45531 5 2.9918 5H21.0082C21.556 5 22 5.44463 22 6.00087V19.9991C22 20.5519 21.5447 21 21.0082 21H2.9918C2.44405 21 2 20.5554 2 19.9991V6.00087ZM4 7V19H20V7H4ZM14 16C15.6569 16 17 14.6569 17 13C17 11.3431 15.6569 10 14 10C12.3431 10 11 11.3431 11 13C11 14.6569 12.3431 16 14 16ZM14 18C11.2386 18 9 15.7614 9 13C9 10.2386 11.2386 8 14 8C16.7614 8 19 10.2386 19 13C19 15.7614 16.7614 18 14 18ZM4 2H10V4H4V2Z"></path>'],
				['label' => __('Armchair'), 'value' => 'armchair', 'icon' => '<path d="M8 3C5.79086 3 4 4.79086 4 7V9.12602C2.27477 9.57006 1 11.1362 1 13C1 14.4817 1.8052 15.7734 3 16.4646V19V21H5V20H19V21H21V19V16.4646C22.1948 15.7734 23 14.4817 23 13C23 11.1362 21.7252 9.57006 20 9.12602V7C20 4.79086 18.2091 3 16 3H8ZM18 9.12602C16.2748 9.57006 15 11.1362 15 13H9C9 11.1362 7.72523 9.57006 6 9.12602V7C6 5.89543 6.89543 5 8 5H16C17.1046 5 18 5.89543 18 7V9.12602ZM9 15H15V16H17V13C17 11.8954 17.8954 11 19 11C20.1046 11 21 11.8954 21 13C21 13.8693 20.4449 14.6114 19.6668 14.8865C19.2672 15.0277 19 15.4055 19 15.8293V18H5V15.8293C5 15.4055 4.73284 15.0277 4.33325 14.8865C3.5551 14.6114 3 13.8693 3 13C3 11.8954 3.89543 11 5 11C6.10457 11 7 11.8954 7 13V16H9V15Z"></path>'],
			];
		}
		return $this->_options;
	}
	
    /**
     * @param string $name
     * @return string
     */
    public function getIcon($name)
    {
        $options = $this->toIconArray();
        $value = '[]';
        if (array_key_exists($name, $options)) {
            $value = $options[$name];
        }

        return $value;
    }

    /**
     * @return array
     */
    public function toIconArray()
    {
        if (empty($this->iconArray)) {
            $this->iconArray = [];
            foreach ($this->getAllOptions() as $item) {
                $this->iconArray[$item['value']] = isset($item['icon']) ? $item['icon'] : '';
            }
        }
        return $this->iconArray;
    }

	/**
	 * Get a text for option value
	 *
	 * @param string|int $value
	 * @return string|false
	 */
	public function getOptionText($value)
	{
		$options = $this->getAllOptions();
		foreach ($options as $option) {
			if ($option['value'] == $value) {
				return $option['label'];
			}
		}
		return false;
	}

}