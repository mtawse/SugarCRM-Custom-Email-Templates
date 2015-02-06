<?php
/**
 * @author Martin Tawse martin.tawse@gmail.com
 * Date: 06/02/2015
 *
 * Custom extension of EmailTemplate
 */

require_once 'modules/EmailTemplates/EmailTemplate.php';

class CustomEmailTemplate extends EmailTemplate
{
    /**
     * Override original s we can call custom parse_template_bean()
     *
     * @param $string
     * @param $bean_arr
     * @return mixed
     */
    function parse_template($string, $bean_arr) {
        global $beanFiles, $beanList;

        foreach($bean_arr as $bean_name => $bean_id) {
            $focus = BeanFactory::getBean($bean_name, $bean_id);

            if($bean_name == 'Leads' || $bean_name == 'Prospects') {
                $bean_name = 'Contacts';
            }

            if(isset($this) && isset($this->module_dir) && $this->module_dir == 'EmailTemplates') {
                $string = $this->parse_template_bean($string, $bean_name, $focus);  // call custom function
            } else {
                // $string = EmailTemplate::parse_template_bean($string, $bean_name, $focus);
                $string = $this->parse_template_bean($string, $bean_name, $focus);  // call custom function
            }
        }
        return $string;
    }


    /**
     * Override original so we can parse additional modules
     *
     * @param $string
     * @param $bean_name
     * @param $focus
     * @return mixed
     */
    function parse_template_bean($string, $bean_name, &$focus) {
        global $current_user;
        global $beanFiles, $beanList;
        $repl_arr = array();

        global $app_list_strings;

        // TAWSE
        // Use parent_type_display for master module list
        $module_list = $app_list_strings['parent_type_display'];
        $ignore_modules = array('Accounts', 'Contacts', 'Leads', 'Prospects', 'Users');
        // TAWSE

        // cn: bug 9277 - create a replace array with empty strings to blank-out invalid vars
        $acct = BeanFactory::getBean('Accounts');
        $contact = BeanFactory::getBean('Contacts');
        $lead = BeanFactory::getBean('Leads');
        $prospect = BeanFactory::getBean('Prospects');

        // TAWSE
        $custom_beans = array();
        foreach ($module_list as $module => $label) {
            if (!in_array($module, $ignore_modules)) {
                $custom_beans[$module] = BeanFactory::getBean($module);
            }
        }
        // TAWSE

        foreach($lead->field_defs as $field_def) {
            if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                continue;
            }
            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                'contact_'         . $field_def['name'] => '',
                'contact_account_' . $field_def['name'] => '',
            ));
        }
        foreach($prospect->field_defs as $field_def) {
            if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                continue;
            }
            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                'contact_'         . $field_def['name'] => '',
                'contact_account_' . $field_def['name'] => '',
            ));
        }
        foreach($contact->field_defs as $field_def) {
            if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                continue;
            }
            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                'contact_'         . $field_def['name'] => '',
                'contact_account_' . $field_def['name'] => '',
            ));
        }
        foreach($acct->field_defs as $field_def) {
            if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                continue;
            }
            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                'account_'         . $field_def['name'] => '',
                'account_contact_' . $field_def['name'] => '',
            ));
        }
        // cn: end bug 9277 fix

        // TAWSE add our additional modules
        foreach ($custom_beans as $bean) {
            foreach($bean->field_defs as $field_def) {
                if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                    continue;
                }
                $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                    strtolower($app_list_strings['moduleListSingular'][$bean->module_dir]).'_'         . $field_def['name'] => '',
                    //'account_contact_' . $field_def['name'] => '',
                ));
            }
        }
        // TAWSE


        // feel for Parent account, only for Contacts traditionally, but written for future expansion
        if(isset($focus->account_id) && !empty($focus->account_id)) {
            $acct->retrieve($focus->account_id);
        }

        if($bean_name == 'Contacts') {
            // cn: bug 9277 - email templates not loading account/opp info for templates
            if(!empty($acct->id)) {
                foreach($acct->field_defs as $field_def) {
                    if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                        continue;
                    }

                    if($field_def['type'] == 'enum') {
                        $translated = translate($field_def['options'], 'Accounts' ,$acct->$field_def['name']);

                        if(isset($translated) && ! is_array($translated)) {
                            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                                'account_'         . $field_def['name'] => $translated,
                                'contact_account_' . $field_def['name'] => $translated,
                            ));
                        } else { // unset enum field, make sure we have a match string to replace with ""
                            $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                                'account_'         . $field_def['name'] => '',
                                'contact_account_' . $field_def['name'] => '',
                            ));
                        }
                    } else {
                        // bug 47647 - allow for fields to translate before adding to template
                        $translated = self::_convertToType($field_def['type'],$acct->$field_def['name']);
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            'account_'         . $field_def['name'] => $translated,
                            'contact_account_' . $field_def['name'] => $translated,
                        ));
                    }
                }
            }

            if(!empty($focus->assigned_user_id)) {
                $user = BeanFactory::getBean('Users', $focus->assigned_user_id);
                $repl_arr = EmailTemplate::_parseUserValues($repl_arr, $user);
            }
        } elseif($bean_name == 'Users') {
            /**
             * This section of code will on do work when a blank Contact, Lead,
             * etc. is passed in to parse the contact_* vars.  At this point,
             * $current_user will be used to fill in the blanks.
             */
            $repl_arr = EmailTemplate::_parseUserValues($repl_arr, $current_user);
        } else {
            // assumed we have an Account in focus
            foreach($contact->field_defs as $field_def) {
                if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name' || $field_def['type'] == 'link') {
                    continue;
                }

                if($field_def['type'] == 'enum') {
                    $translated = translate($field_def['options'], 'Accounts' ,$contact->$field_def['name']);

                    if(isset($translated) && ! is_array($translated)) {
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            'contact_'         . $field_def['name'] => $translated,
                            'contact_account_' . $field_def['name'] => $translated,
                        ));
                    } else { // unset enum field, make sure we have a match string to replace with ""
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            'contact_'         . $field_def['name'] => '',
                            'contact_account_' . $field_def['name'] => '',
                        ));
                    }
                } else {
                    if (isset($contact->$field_def['name'])) {
                        // bug 47647 - allow for fields to translate before adding to template
                        $translated = self::_convertToType($field_def['type'],$contact->$field_def['name']);
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            'contact_'         . $field_def['name'] => $translated,
                            'contact_account_' . $field_def['name'] => $translated,
                        ));
                    } // if
                }
            }
        }

        ///////////////////////////////////////////////////////////////////////
        ////	LOAD FOCUS DATA INTO REPL_ARR
        foreach($focus->field_defs as $field_def) {
            if(isset($focus->$field_def['name'])) {
                if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
                    continue;
                }

                if($field_def['type'] == 'enum' && isset($field_def['options'])) {
                    $translated = translate($field_def['options'],$bean_name,$focus->$field_def['name']);

                    if(isset($translated) && ! is_array($translated)) {
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            strtolower($beanList[$bean_name])."_".$field_def['name'] => $translated,
                        ));
                    } else { // unset enum field, make sure we have a match string to replace with ""
                        $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                            strtolower($beanList[$bean_name])."_".$field_def['name'] => '',
                        ));
                    }
                } else {
                    // bug 47647 - translate currencies to appropriate values
                    $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                        strtolower($beanList[$bean_name])."_".$field_def['name'] => self::_convertToType($field_def['type'],$focus->$field_def['name']),
                    ));
                }
            } else {
                if($field_def['name'] == 'full_name') {
                    $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                        strtolower($beanList[$bean_name]).'_full_name' => $focus->get_summary_text(),
                    ));
                } else {
                    $repl_arr = EmailTemplate::add_replacement($repl_arr, $field_def, array(
                        strtolower($beanList[$bean_name])."_".$field_def['name'] => '',
                    ));
                }
            }
        } // end foreach()

        krsort($repl_arr);
        reset($repl_arr);
        //20595 add nl2br() to respect the multi-lines formatting
        if(isset($repl_arr['contact_primary_address_street'])){
            $repl_arr['contact_primary_address_street'] = nl2br($repl_arr['contact_primary_address_street']);
        }
        if(isset($repl_arr['contact_alt_address_street'])){
            $repl_arr['contact_alt_address_street'] = nl2br($repl_arr['contact_alt_address_street']);
        }

        foreach ($repl_arr as $name=>$value) {
            if($value != '' && is_string($value)) {
                $string = str_replace("\$$name", $value, $string);
            } else {
                $string = str_replace("\$$name", ' ', $string);
            }
        }

        return $string;
    }
} 