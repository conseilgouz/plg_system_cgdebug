<?php
/**
* CG Debug  - Joomla 4.x/5.x Plugin
* @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
* @copyright (c) 2025 ConseilGouz. All Rights Reserved.
* @author ConseilGouz
**/
namespace ConseilGouz\Plugin\System\CGDebug\Rule;
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\Rule\PasswordRule;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class PassdebugRule extends PasswordRule
{

	public function test(\SimpleXMLElement $element, $value, $group = null, Registry $input = null,Form $form = null) {

        if (!empty($value)) {
           $nHits = preg_match_all('/[%&]/', $value, $imatch);
           if ($nHits > 0) {
                Factory::getApplication()->enqueueMessage(Text::_('CG_DEBUG_INVALID_SPECIALS'),'error');
                return false;
           }
        }
        return parent::test($element, $value, $group, $input,$form);
		
	}
}