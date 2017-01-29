<?php

namespace p3ym4n\FormMaker;

use App\File;
use App\Http\Assets\AutoSizeAsset;
use App\Http\Assets\CKEditorAsset;
use App\Http\Assets\ColorPickerAsset;
use App\Http\Assets\DatePickerAsset;
use App\Http\Assets\PersianDateAsset;
use App\Http\Assets\PriceFormatAsset;
use App\Http\Assets\SelectAjaxAsset;
use App\Http\Assets\SelectAsset;
use App\Http\Assets\SortableAsset;
use App\Http\Assets\TagsInputAsset;
use Asset;
use Illuminate\Database\Eloquent\Model;
use p3ym4n\JDte\JDate;
use SplFileInfo;

/**
 * Class FormMaker
 * @package p3ym4n\FormMaker
 */
final Class FormMaker {
    
    /**
     * the supported http verbs in the form
     * if not in the list the method will be 'POST'
     * @var array
     */
    public static $verbs = ['GET', 'POST', 'PUT', 'DELETE'];
    
    /**
     * the default name for input elements when the $this->namesInArray(true) && $this->model is null
     * @var string
     */
    public static $defaultInputName = 'input';
    
    /**
     * the name use for element s name when in array
     * @var
     */
    private $name;
    
    /**
     * the url that form should submitted to
     * @var string
     */
    private $url;
    
    /**
     * the model that form builds on its attributes
     * @var Model
     */
    private $model;
    
    /**
     * the http verb that form should submit with that
     * @var string
     */
    private $method;
    
    /**
     * the extra input for spoofing http verbs if not get or post
     * @var string
     */
    private $methodField;
    
    /**
     * the redirect url that form should redirect to it after submit
     * if not set the form will not redirect
     * @var string
     */
    private $redirect;
    
    /**
     * holding the form generated htmls
     * @var string
     */
    private $form;
    
    /**
     * the autocomplete attribute of the form element it self
     * @var string
     */
    private $autoComplete = 'off';
    
    /**
     * label elements classes for sizing
     * @var string
     */
    private $labelClass = 'col-xs-2';
    
    /**
     * holder divs classes for sizing
     * @var string
     */
    private $holderClass = 'col-xs-10';
    
    /**
     * prefix for elements id attribute
     * @var string
     */
    private $idPrefix;
    
    /**
     * prefix for elements class attribute
     * @var string
     */
    private $classPrefix;
    
    /**
     * show that the submit buttons have been added to the form or not
     * @var bool
     */
    private $btnAdded = false;
    
    /**
     * if true the form elements name attribute will be in an array
     * @var bool
     */
    private $namesInArray = false;
    
    /**
     * FormMaker constructor.
     *
     * @param string      $url
     * @param Model|null  $model
     * @param string|null $method
     * @param string|null $redirect
     */
    public function __construct($url = '', Model $model = null, $method = null, $redirect = null) {
        
        $this->setUrl($url);
        $this->setModel($model);
        $this->setMethod($method);
        $this->setRedirect($redirect);
    }
    
    /**
     * @param string      $url
     * @param Model|null  $model
     * @param string|null $method
     * @param string|null $redirect
     *
     * @return FormMaker
     */
    public static function create($url = '', Model $model = null, $method = null, $redirect = null) {
        
        return new static($url, $model, $method, $redirect);
    }
    
    /**
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addStatic($name, $options = []) {
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <p id="' . $mains['id'] . '" class="form-control-static" >' . $mains['value'] . '</p>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        return $this;
    }
    
    /**
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addHidden($name, $options = []) {
        
        $mains = $this->main($name, $options);
        $this->form .= '<input type="hidden" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="' . $mains['value'] . '"  ' . $this->extra($mains, $options) . ' >';
        
        return $this;
    }
    
    /**
     * can have these extra options : type , prefix , suffix , info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addText($name, $options = []) {
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        $suffix = '';
        if (isset($options['suffix'])) {
            $suffix = '  <span class="input-group-addon">' . $options['suffix'] . '</span>';
            unset($options['suffix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $type = 'text';
        if (isset($options['type'])) {
            $type = $options['type'];
            unset($options['type']);
        }
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                ' . $openDiv . '
                                ' . $prefix . '
                                <input type="' . $type . '" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="' . $mains['value'] . '" ' . $this->extra($mains, $options) . ' >
                                ' . $suffix . '
                                ' . $closeDiv . '
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        return $this;
    }
    
    /**
     * can have these extra options : type , prefix , suffix , info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addColor($name, $options = []) {
        
        ColorPickerAsset::add();
        
        unset($options['suffix']);
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $suffix = '  <span class="input-group-addon"><i></i></span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        if ( ! isset($options['dir'])) {
            $options['dir'] = 'ltr';
        }
        $align = 'left';
        if ($options['dir'] == 'rtl') {
            $align = 'right';
        }
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . ' colorpicker-component">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="' . $mains['value'] . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        Asset::addScript("
			$('#{$mains['id']}-div .colorpicker-component').colorpicker({
				align : '{$align}',
			    format: 'hex'
			});
		");
        
        return $this;
    }
    
    /**
     * can have these extra options : type , prefix , suffix , info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addPrice($name, $options = []) {
        
        PriceFormatAsset::add();
        
        unset($options['suffix']);
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $currency = 'تومان';
        if (defined('CURRENCY')) {
            $currency = CURRENCY;
        }
        
        $suffix = '  <span class="input-group-addon">' . $currency . '</span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        if ( ! isset($options['dir'])) {
            $options['dir'] = 'ltr';
        }
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="' . $mains['value'] . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        Asset::addScript("
			$('#{$mains['id']}').priceFormat({
			    prefix: '',
                thousandsSeparator: ',',
                centsSeparator: '' ,
                centsLimit : 0
            });
		");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addTags($name, $options = []) {
        
        TagsInputAsset::add();
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="' . $mains['value'] . '" ' . $this->extra($mains, $options) . ' >
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        Asset::addScript("$('#{$mains['id']}').tagsinput();");
        
        return $this;
    }
    
    /**
     * can have these extra options : prefix , suffix , info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addPassword($name, $options = []) {
        
        unset($options['suffix']);
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $suffix = '  <span class="input-group-addon"><i class="fa fa-lg fa-fw fa-lock"></i></span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="password" id="' . $mains['id'] . '" name="' . $mains['name'] . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addTextArea($name, $options = []) {
        
        AutoSizeAsset::add();
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <textarea id="' . $mains['id'] . '" name="' . $mains['name'] . '" ' . $this->extra($mains, $options) . ' >' . $mains['value'] . '</textarea>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addEditor($name, $options = []) {
        
        CKEditorAsset::add();
        
        $mains = $this->main($name, $options);
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <textarea id="' . $mains['id'] . '" name="' . $mains['name'] . '" ' . $this->extra($mains, $options) . ' >' . $mains['value'] . '</textarea>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        Asset::addScript("$(document).ready(function(){
            $('#" . $mains['id'] . "').ckeditor();
        });");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addDate($name, $options = []) {
        
        PersianDateAsset::add();
        DatePickerAsset::add();
        
        unset($options['suffix']);
        $options['dir'] = 'ltr';
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $suffix = '  <span class="input-group-addon"><i class="fa fa-lg fa-fw fa-calendar"></i></span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        $mains = $this->main($name, $options);
        
        $value = $mains['value'];
        if ( ! empty($value)) {
            if ($value instanceof Carbon\Carbon) {
                $value = JDate::createFromCarbon($value)->format(FORMAT_ONLY_DATE);
            } else {
                $value = JDate::createFromFormat(FORMAT_ONLY_DATE, $value)->format(FORMAT_ONLY_DATE);
            }
            list($year, $month, $day) = explode('/', $value);
            $value = json_encode([$year, $month, $day], JSON_NUMERIC_CHECK);
        }
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="" data-value="' . $value . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        $jsVariable = studly_case($mains['id']);
        Asset::addScript("
            
            var {$jsVariable}Element = $('#{$mains['id']}');
            var {$jsVariable}tempCheck = {$jsVariable}Element.val();
            {$jsVariable}Element.pDatepicker({
                autoClose : true,
                timePicker: {
                    enabled: false
                },
                navigator : {
                    text: {
                        btnNextText: \"<i class='fa fa-lg fa-angle-left'></i>\",
                        btnPrevText: \"<i class='fa fa-lg fa-angle-right'></i>\"
                    },
                },
                formatter: function (unixDate) {
                    var pdate = new persianDate(unixDate);
                    pdate.formatPersian = false;
                    return pdate.format('YYYY/MM/DD');
                },
                toolbox: {
                    text: {
                        btnToday: \"امروز\"
                    }
                },
            });
            
            if({$jsVariable}tempCheck.length == 0){
                {$jsVariable}Element.val('');
            } 
            if({$jsVariable}Element.data('value') !=  undefined){
                try{
                    {$jsVariable}Element.pDatepicker(\"setDate\", {$jsVariable}Element.data('value'));
                }catch(e){
                    console.log(e.message);
                }
            }
        ");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addDateTime($name, $options = []) {
        
        PersianDateAsset::add();
        DatePickerAsset::add();
        
        unset($options['suffix']);
        $options['dir'] = 'ltr';
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $suffix = '  <span class="input-group-addon"><i class="fa fa-lg fa-fw fa-calendar"></i></span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        $mains = $this->main($name, $options);
        
        $value = $mains['value'];
        if ( ! empty($value)) {
            if ($value instanceof Carbon\Carbon) {
                $value = JDate::createFromCarbon($value)->format(FORMAT_ONLY_DATE);
            } else {
                $value = JDate::createFromFormat(FORMAT_ONLY_DATE, $value)->format(FORMAT_ONLY_DATE);
            }
            $dates = explode(' ', $value);
            if ( ! isset($dates[1])) {
                $dates[1] = '00:00:00';
            }
            list($year, $month, $day) = explode('/', $dates[0]);
            list($hour, $minute, $second) = explode(':', $dates[1]);
            
            $value = json_encode([$year, $month, $day, $hour, $minute, $second], JSON_NUMERIC_CHECK);
        }
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="" data-value="' . $value . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        $jsVariable = studly_case($mains['id']);
        Asset::addScript("
            
            var {$jsVariable}Element = $('#{$mains['id']}');
            var {$jsVariable}tempCheck = {$jsVariable}Element.val();
            {$jsVariable}Element.pDatepicker({
                autoClose : true,
                timePicker: {
                    enabled: true
                },
                navigator : {
                    text: {
                        btnNextText: \"<i class='fa fa-lg fa-angle-left'></i>\",
                        btnPrevText: \"<i class='fa fa-lg fa-angle-right'></i>\"
                    },
                },
                formatter: function (unixDate) {
                    var pdate = new persianDate(unixDate);
                    pdate.formatPersian = false;
                    return pdate.format('YYYY/MM/DD HH:mm:ss');
                },
                toolbox: {
                    text: {
                        btnToday: \"هم اکنون\"
                    }
                },
            });
            
            if({$jsVariable}tempCheck.length == 0){
                {$jsVariable}Element.val('');
            } 
            if({$jsVariable}Element.data('value') !=  undefined){
                try{
                    {$jsVariable}Element.pDatepicker(\"setDate\", {$jsVariable}Element.data('value'));
                }catch(e){
                    console.log(e.message);
                }
            }
        ");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addTime($name, $options = []) {
        
        PersianDateAsset::add();
        DatePickerAsset::add();
        
        unset($options['suffix']);
        $options['dir'] = 'ltr';
        
        //the prefix and suffix
        $prefix = '';
        $openDiv = '';
        $closeDiv = '';
        if (isset($options['prefix'])) {
            $prefix = '  <span class="input-group-addon">' . $options['prefix'] . '</span>';
            unset($options['prefix']);
            $openDiv = '<div class="input-group">';
            $closeDiv = '</div>';
        }
        
        $suffix = '  <span class="input-group-addon"><i class="fa fa-lg fa-fw fa-history"></i></span>';
        $openDiv = '<div class="input-group">';
        $closeDiv = '</div>';
        
        $mains = $this->main($name, $options);
        
        $value = $mains['value'];
        if ( ! empty($value)) {
            if ($value instanceof Carbon\Carbon) {
                $value = JDate::createFromCarbon($value)->format(FORMAT_ONLY_DATE);
            } else {
                $value = JDate::createFromFormat(FORMAT_ONLY_DATE, $value)->format(FORMAT_ONLY_DATE);
            }
            list($hour, $minute, $second) = explode(':', $value);
            
            $value = json_encode([$hour, $minute, $second], JSON_NUMERIC_CHECK);
        }
        
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <label for="' . $mains['id'] . '" class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                    ' . $openDiv . '
                                    ' . $prefix . '
                                    <input type="text" id="' . $mains['id'] . '" name="' . $mains['name'] . '" value="" data-value="' . $value . '" ' . $this->extra($mains, $options) . ' >
                                    ' . $suffix . '
                                    ' . $closeDiv . '
                                    ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        $jsVariable = studly_case($mains['id']);
        Asset::addScript("
            
            var {$jsVariable}Element = $('#{$mains['id']}');
            var {$jsVariable}tempCheck = {$jsVariable}Element.val();
            {$jsVariable}Element.pDatepicker({
                autoClose : true,
                onlyTimePicker: true,
                timePicker: {
                    enabled: true
                },
                navigator : {
                    text: {
                        btnNextText: \"<i class='fa fa-lg fa-angle-left'></i>\",
                        btnPrevText: \"<i class='fa fa-lg fa-angle-right'></i>\"
                    },
                },
                formatter: function (unixDate) {
                    var pdate = new persianDate(unixDate);
                    pdate.formatPersian = false;
                    return pdate.format('HH:mm:ss');
                },
                toolbox: {
                    text: {
                        btnToday: \"هم اکنون\"
                    }
                },
            });
            
            if({$jsVariable}tempCheck.length == 0){
                {$jsVariable}Element.val('');
            } 
            if({$jsVariable}Element.data('value') !=  undefined){
                try{
                    {$jsVariable}Element.pDatepicker(\"setDate\", {$jsVariable}Element.data('value'));
                }catch(e){
                    console.log(e.message);
                }
            }
        ");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addPic($name, $options = []) {
        
        unset($options['multiple'], $options['max']);
        
        $this->libraryIntegratorCommonAssets();
        
        //getting the width & height
        $height = 78;
        $width = 78;
        if (isset($options['height'])) {
            $height = (int) $options['height'];
            $width = $height;
        }
        if (isset($options['width'])) {
            $width = (int) $options['width'];
        }
        
        //getting the main attributes
        $mains = $this->main($name, $options);
        
        //removing the form-control from class
        $mains['class'] = str_replace('form-control ', '', $mains['class']);
        
        //making a unique names
        $rawName = str_replace(['[', ']'], '', studly_case($mains['name']));
        $delFunction = 'del' . $rawName;
        
        $classHaveImg = '';
        $imgPath = '';
        if ( ! empty($mains['value'])) {
            $imgPath = config('filesystems.disks.local.url') . $mains['value'];
            $classHaveImg = 'haveImg';
        }
        
        $dirToGo = pathinfo($mains['value'], PATHINFO_DIRNAME);
        if ($dirToGo == DS) {
            $dirToGo = '';
        }
        
        //the main html part
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
            <div class="' . $this->labelClass . '">
                <label for="' . $mains['id'] . '" class="control-label pull-left">' . $mains['title'] . '</label>
                <div class="clearfix"></div>
            </div>
            <div class="' . $this->holderClass . '">
                <div class="clearfix" id="' . $mains['id'] . '-inner-div">
                    <div class="imgBox ' . $classHaveImg . '" id="' . $mains['id'] . '0-parent" >
						<button type="button" class="btn btn-danger btn-sm boxPurger tooltips" title="' . trans('messages.delete') . '" onclick="' . $delFunction . '(this);" >
							<i class="fa fa-trash-o"></i>
						</button>
						<button type="button" onclick="callLibrary(\'' . $mains['id'] . '0\' , \'' . File::TYPE_IMAGE . '\' , \'' . $dirToGo .
                       '\');" class="btn btn-default imgSelector tooltips" title="' . trans('messages.click to choose file') . '" data-placement="left" >
							<img src="' . $imgPath . '" />
							<i class="fa fa-file-image-o"></i>
							<input type="hidden" id="' . $mains['id'] . '0" name="' . $mains['name'] . '" value="' . $mains['value'] . '" >
						</button>
					</div>
                </div>
                ' . $mains['info'] . '
                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
            </div>
        </div>';
        
        Asset::addScript("
			//removes select boxes
			function {$delFunction}(elm){
				$(elm).parent().removeClass('haveImg');
				var mainBtn = $(elm).siblings();
				mainBtn.children('img').attr('src' , '');
				mainBtn.children('input').val('');
				$('.tooltip').remove();
			}
		");
        
        //font icon customization
        $fontHeight = $height / 2;
        Asset::addStyle("
			#{$mains['id']}-inner-div{
				margin-right: -5px;
				margin-left: -5px;
			}
			#{$mains['id']}-inner-div .imgBox{
				height: {$height}px;
				min-width: {$width}px;
				width: auto;
				margin-bottom: 0px;
			}
			#{$mains['id']}-inner-div .imgSelector .fa{
				font-size: {$fontHeight}px;
			}
		");
        
        return $this;
    }
    
    /**
     * can have these extra options : info , max
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addMultiPic($name, $options = []) {
        
        SortableAsset::add();
        $this->libraryIntegratorCommonAssets();
        $options['multiple'] = true;
        
        //getting the width & height
        $height = 78;
        $width = 78;
        if (isset($options['height'])) {
            $height = (int) $options['height'];
            $width = $height;
        }
        if (isset($options['width'])) {
            $width = (int) $options['width'];
        }
        
        //the maximum and multiple
        $max = 0;
        if (isset($options['max'])) {
            $max = (int) $options['max'];
        }
        
        //getting the main attributes
        
        $mains = $this->main($name, $options);
        
        //removing the form-control from class
        $mains['class'] = str_replace('form-control ', '', $mains['class']);
        
        //making a unique names
        $rawName = str_replace(['[', ']'], '', studly_case($mains['name']));
        $addFunction = 'add' . $rawName;
        $delFunction = 'del' . $rawName;
        $indexMax = 'max' . $rawName;
        $adder = 'btn' . $rawName;
        
        //the main html part
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <div class="' . $this->labelClass . '">
                                <label for="' . $mains['id'] . '" class="control-label pull-left">' . $mains['title'] . '</label>
                                <div class="clearfix"></div>
                                <button id="' . $adder . '" type="button" class="btn btn-info tooltips btn-add" data-placement="bottom" title="' . trans('words.add pic') . '" onclick="' .
                       $addFunction . '(\'' . $mains['id'] . '\' , \'' . $mains['name'] . '\' , \'\', \'\', \'\' );" >
					                <i class="fa fa-lg fa-plus"></i>
					            </button>
                            </div>
                            <div class="' . $this->holderClass . '">
                                <div class="clearfix" id="' . $mains['id'] . '-inner-div"></div>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        //getting the values and generating the exclusive part
        $values = $mains['value'];
        if ( ! is_array($values)) {
            $values = explode(',', $values);
        }
        $callScript = '';
        foreach ($values as $value) {
            if ( ! empty($value)) {
                $imgPath = url('/upload') . $value;
                $dirToGo = pathinfo($value, PATHINFO_DIRNAME);
                if ($dirToGo == DS) {
                    $dirToGo = '';
                }
                $callScript .= " {$addFunction}('{$mains['id']}' , '{$mains['name']}' , '{$value}' , '{$imgPath}', '{$dirToGo}'); ";
            }
        }
        Asset::addScript("
			var picIndex = 1;
			var {$indexMax} = {$max};
			
			//removes select boxes
			function {$delFunction}(elm){
				$('#{$adder}').slideDown();
				$(elm).parent().remove();
				$('.tooltip').remove();
			}
			
			//add a select box
			function {$addFunction}(div , name , img , path , toGo){
				
				var count = $('#' + div + '-inner-div > .imgBox').length;
				
				" . ($max == 0 ? "" : "if({$indexMax} > count ){") . "
					var classHaveImg = 'haveImg';
					if(typeof(img) == 'undefined' || img == ''){
						img = '';
						classHaveImg = '';
					} 
					
					$('#' + div + '-inner-div').append('<div class=\"imgBox ' + classHaveImg + '\" id=\"' + div + picIndex + '-parent\" >'+
									'<button type=\"button\" class=\"btn btn-danger btn-sm boxRemover \" title=\"" . trans('messages.delete') . "\" onclick=\"{$delFunction}(this);\" >'+
				                        '<i class=\"fa fa-trash-o\"></i>'+
				                    '</button>'+
				                    '<button type=\"button\" onclick=\"callLibrary(\'' + div + picIndex + '\' , \'" . File::TYPE_IMAGE .
                         "\' , \'' + toGo + '\' );\" class=\"btn btn-default imgSelector \" title=\"" . trans('messages.click to choose file') . "\" data-placement=\"left\" >'+
					                    '<img src=\"'+path+'\" />'+
					                    '<i class=\"fa fa-file-image-o\"></i>'+
					                    '<input type=\"hidden\" id=\"' + div + picIndex + '\" name=\"' + name + '\" value=\"' + img + '\" >'+
				                    '</button>'+
								'</div>');
					
					//$('.tooltips').tooltip();
					
					picIndex++;	
				
				" . ($max == 0 ? "" : "if({$indexMax} == count+1){ $('#{$adder}').slideUp();}") . "
				" . ($max == 0 ? "" : "}") . "
				
				$('#{$mains['id']}-inner-div').sortable({
				    items : '.imgBox',
				    forcePlaceholderSize: true
			    });
			}
			
			{$callScript}
		");
        
        //font icon customization
        $fontHeight = $height / 2;
        Asset::addStyle("
			#{$mains['id']}-inner-div{
				margin-right: -5px;
				margin-left: -5px;
			}
			#{$mains['id']}-inner-div .sortable-placeholder{
				height: {$height}px!important;
				width: {$width}px!important;
				float: right;
			}
			#{$mains['id']}-inner-div .imgBox{
				min-width: {$width}px;
				height: {$height}px;
			}
			#{$mains['id']}-inner-div .imgSelector .fa{
				font-size: {$fontHeight}px;
			}
			
		");
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addFile($name, $options = []) {
        
        unset($options['multiple'], $options['max']);
        
        $this->libraryIntegratorCommonAssets();
        
        if ( ! isset($options['types']) || empty($options['types'])) {
            $options['types'] = array_keys(File::getTypeList()->toArray());
        }
        $options['types'] = implode(' ', $options['types']);
        
        //getting the main attributes
        $mains = $this->main($name, $options);
        
        //removing the form-control from class
        $mains['class'] = str_replace('form-control ', '', $mains['class']);
        
        //making a unique names
        $rawName = str_replace(['[', ']'], '', studly_case($mains['name']));
        $delFunction = 'del' . $rawName;
        
        $classHaveFile = '';
        $icon = 'fa-file';
        $dirToGo = '';
        if ( ! empty($mains['value'])) {
            
            $dirToGo = pathinfo($mains['value'], PATHINFO_DIRNAME);
            if ($dirToGo == DS) {
                $dirToGo = '';
            }
            $basePath = config('filesystems.disks.local.root');
            $spl = new SplFileInfo($basePath . $mains['value']);
            $icon = File::getType(File::typeDetect($spl), 'icon');
            $classHaveFile = 'haveFile';
        }
        
        //the main html part
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
            <div class="' . $this->labelClass . '">
                <label for="' . $mains['id'] . '" class="control-label pull-left">' . $mains['title'] . '</label>
                <div class="clearfix"></div>
            </div>
            <div class="' . $this->holderClass . '">
                <div class="clearfix" id="' . $mains['id'] . '-inner-div">
                    <div class="fileBox ' . $classHaveFile . '" id="' . $mains['id'] . '0-parent" >
						<button type="button" class="btn btn-danger btn-sm boxPurger tooltips" title="' . trans('messages.delete') . '" onclick="' . $delFunction . '(this);" >
							<i class="fa fa-trash-o"></i>
						</button>
						<button type="button" onclick="callLibrary(\'' . $mains['id'] . '0\' , \'' . $options['types'] . '\' , \'' . $dirToGo .
                       '\');" class="btn btn-default fileSelector tooltips" title="' . trans('messages.click to choose file') . '" data-placement="left" >
							<p dir="ltr" >' . $mains['value'] . '</p>
							<i class="fa fa-lg ' . $icon . '"></i>
							<input type="hidden" id="' . $mains['id'] . '0" name="' . $mains['name'] . '" value="' . $mains['value'] . '" >
						</button>
					</div>
                </div>
                ' . $mains['info'] . '
                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
            </div>
        </div>';
        
        Asset::addScript("
			//removes select boxes
			function {$delFunction}(elm){
				$(elm).parent().removeClass('haveFile');
				var mainBtn = $(elm).siblings();
				mainBtn.children('p').text('" . trans('words.choose') . "');
				mainBtn.children('i').attr('class' , 'fa fa-lg fa-file');
				mainBtn.children('input').val('');
				$('.tooltip').remove();
			}
		");
        
        Asset::addStyle("
			#{$mains['id']}-inner-div .fileBox{
				margin-bottom: 0px;
			}
		");
        
        return $this;
    }
    
    /**
     * can have these extra options : info , max
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addMultiFile($name, $options = []) {
        
        $options['multiple'] = true;
        SortableAsset::add();
        $this->libraryIntegratorCommonAssets();
        
        if ( ! isset($options['types']) || empty($options['types'])) {
            $options['types'] = array_keys(File::getTypeList()->toArray());
        }
        if (is_array($options['types'])) {
            $options['types'] = implode(' ', $options['types']);
        }
        
        //the maximum and multiple
        $max = 0;
        if (isset($options['max'])) {
            $max = (int) $options['max'];
        }
        
        //getting the main attributes
        $mains = $this->main($name, $options);
        
        //removing the form-control from class
        $mains['class'] = str_replace('form-control ', '', $mains['class']);
        
        //making a unique names
        $rawName = str_replace(['[', ']'], '', studly_case($mains['name']));
        $addFunction = 'add' . $rawName;
        $delFunction = 'del' . $rawName;
        $indexMax = 'max' . $rawName;
        $adder = 'btn' . $rawName;
        
        //the main html part
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div">
                            <div class="' . $this->labelClass . '">
                                <label for="' . $mains['id'] . '" class="control-label pull-left">' . $mains['title'] . '</label>
                                <div class="clearfix"></div>
                                <button id="' . $adder . '" type="button" class="btn btn-info tooltips btn-add" data-placement="bottom" title="' . trans('words.add file') . '" onclick="' .
                       $addFunction . '(\'' . $mains['id'] . '\' , \'' . $mains['name'] . '\' , \'\', \'\', \'\' );" >
					                <i class="fa fa-lg fa-plus"></i>
					            </button>
                            </div>
                            <div class="' . $this->holderClass . '">
                                <div class="clearfix" id="' . $mains['id'] . '-inner-div">   
                                </div>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        //getting the values and generating the exclusive part
        $basePath = config('filesystems.disks.local.root');
        $values = $mains['value'];
        if ( ! is_array($values)) {
            $values = explode(',', $values);
        }
        $callScript = '';
        foreach ($values as $value) {
            if ( ! empty($value)) {
                $spl = new SplFileInfo($basePath . $value);
                $dirToGo = pathinfo($value, PATHINFO_DIRNAME);
                if ($dirToGo == DS) {
                    $dirToGo = '';
                }
                $icon = File::getType(File::typeDetect($spl), 'icon');
                $callScript .= " {$addFunction}('{$mains['id']}' , '{$mains['name']}' , '{$value}' , '{$icon}' , '{$dirToGo}'); ";
            }
        }
        
        Asset::addScript("
			var fileIndex = 1;
			var {$indexMax} = {$max};
			
			//removes select boxes
			function {$delFunction}(elm){
				$('#{$adder}').slideDown();
				$(elm).parent().remove();
				$('.tooltip').remove();
			}
			
			//add a select box
			function {$addFunction}(div , name , file , icon, toGo){
				
				var count = $('#' + div + '-inner-div > .fileBox').length;
				
				" . ($max == 0 ? "" : "if({$indexMax} > count ){") . "
					var classHaveFile = 'haveFile';
					var title = file;
					if(typeof(file) == 'undefined' || file == ''){
						file = '';
						title = '" . trans('words.choose') . "';
						classHaveFile = '';
						icon = 'fa-file';
					}
					
					$('#' + div + '-inner-div').append('<div class=\"fileBox ' + classHaveFile + '\" id=\"' + div + fileIndex + '-parent\" >'+
									'<button type=\"button\" class=\"btn btn-danger btn-sm boxRemover \" title=\"" . trans('messages.delete') . "\" onclick=\"{$delFunction}(this);\" >'+
				                        '<i class=\"fa fa-trash-o\"></i>'+
				                    '</button>'+
				                    '<button type=\"button\" onclick=\"callLibrary(\'' + div + fileIndex + '\' , \'{$options['types']}\' ,  \'' + toGo + '\' );\" class=\"btn btn-default fileSelector \" title=\"" .
                         trans('messages.click to choose file') . "\" data-placement=\"left\" >'+
					                    '<p dir=\"ltr\" >' + title + '</p>'+
					                    '<i class=\"fa fa-lg ' + icon + '\"></i>'+
					                    '<input type=\"hidden\" id=\"' + div + fileIndex + '\" name=\"' + name + '\" value=\"' + file + '\" >'+
				                    '</button>'+
								'</div>');
					
					//$('.tooltips').tooltip();
					
					fileIndex++;	
				
				" . ($max == 0 ? "" : "if({$indexMax} == count+1){ $('#{$adder}').slideUp();}") . "
				" . ($max == 0 ? "" : "}") . "
				
				//reloading the sortable
				$('#{$mains['id']}-inner-div').sortable({
				    items : '.fileBox',
				    forcePlaceholderSize: true
			    });
			}
			
			{$callScript}
		");
        
        return $this;
    }
    
    /**
     * load the common assets needed for a pic or file selector
     */
    private function libraryIntegratorCommonAssets() {
        
        $host = url('/');
        Asset::addScript("
			//a global variable for holding for library integrates
			var selectiveId = null;
			
			//getting messages from the parent forms
			self.addEventListener('message',function(e) {
				if(e.origin !== '{$host}') {
					return;
				}
				
				var url = e.data.url;
				var path = e.data.path;
				var type = e.data.type;
				var icon = e.data.icon;
				
				//detecting the parent type
				if($('#' + selectiveId + '-parent').hasClass('imgBox')){
					
					$('#' + selectiveId).val(path);
					$('#' + selectiveId + '-parent').addClass('haveImg');
					$('#' + selectiveId + '-parent img').attr('src',url);
					
				} else {
					
					$('#' + selectiveId).val(path);
					$('#' + selectiveId + '-parent').addClass('haveFile');
					$('#' + selectiveId).siblings('i').attr('class','fa fa-lg '+ icon);
					$('#' + selectiveId + '-parent p').text(path);
				}
				
				//we reset the selector id
				selectiveId = null;
				
			},false);
			
			//calls the library to select a file with the given type(s)
			function callLibrary(id , type , path){
				//defining the iFrame variable
				var uploadFrame = document.getElementById('library-iframe');
				if(typeof path == 'undefined'){
					path = '';				
				}
				
				selectiveId = id;
				
				uploadFrame.contentWindow.postMessage({
					type : type,  //we send the type of file that can be selected
					path : path   //a relative path for going to the folder
				} , '{$host}');
				
				$('#library-modal').modal('show');
				
				//listen to modal hide once every time
				$('#library-modal').one('hidden.bs.modal', function(){
					var innerDoc = uploadFrame.contentDocument || uploadFrame.contentWindow.document;
					$(innerDoc.getElementById('inWrapper')).removeClass('selective');
					$(innerDoc.getElementById('bunch-group')).show();
				});	
			}
		");
        
        Asset::addStyle("
			/*styles for pics*/
			.btn-add{
				float: left;
				margin-top: 15px;
				padding: 4px;
			}
			.imgBox{
				float: right;
				margin: 0px 5px 10px 5px;
				position: relative;
			}
			.imgBox img{
				display: none;
				width: auto;
				height: 100%;
				max-width: 100%;
			}
			.imgBox .imgSelector{
				padding: 0px;
				height: 100%;
				width: 100%;
			}
			.imgBox .boxRemover{
				padding: 1px 4px 0px 4px;
				position: absolute;
				top: 3px;
				left: 3px;
				display:none;
			}
			.imgBox:hover .boxRemover{
				display: block!important;
			}
			.imgBox .boxPurger{
				padding: 1px 4px 0px 4px;
				position: absolute;
				top: 3px;
				left: 3px;
				display:none;
			}
			.imgBox.haveImg:hover .boxPurger{
				display: block!important;
			}
			.imgBox.haveImg img{
				display: block;
			}
			.imgBox.haveImg .imgSelector i.fa{
				display: none;
			}
			.imgBox .imgSelector i.fa{
				margin: 0 auto;
			}
			
			/*styles for files*/
			.fileBox{
				height: 35px;
				width: 100%;
				margin: 0px auto 10px auto;
				position: relative;
			}
			.fileBox .fileSelector{
				padding: 0px;
				height: 100%;
				width: 100%;
			}
		
			.fileBox .boxPurger{
				position: absolute;
				padding: 1px 4px 0px 4px;
				top: 7px;
				left: 9px;
				display:none;
				z-index: 10;
			}
			.fileBox:hover .boxPurger{
				display: block!important;
			}
			.fileBox .boxRemover{
				position: absolute;
				padding: 1px 4px 0px 4px;
				top: 7px;
				left: 9px;
				display:none;
				z-index: 10;
			}
			.fileBox:hover .boxRemover{
				display: block!important;
			}
			.fileBox .fileSelector i.fa{
				position: absolute;
				top: 10px;
				left: 10px;
			}
			.fileBox .fileSelector p{
	            margin-bottom: 0px;
			    text-align: left;
			    line-height: 33px;
			    padding-left: 40px;
			    text-overflow: ellipsis;
			    width: 100%;
			    overflow-x: hidden;
			}
        ");
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addRadio($name, $options = []) {
        
        $mains = $this->main($name, $options);
        $mains['class'] = str_replace('form-control', '', $mains['class']);
        
        if (isset($options['list'])) {
            $checked = $mains['value'];
            $mod = 12 % count($options['list']);
            $colClass = 'btn btn-default';
            if ($mod == 0) {
                $colClass .= ' col-xs-' . (12 / count($options['list']));
            }
            
            $temp = '<div class="btn-group col-xs-12" data-toggle="buttons">';
            foreach ($options['list'] as $value => $item) {
                
                $checkClass = '';
                $checkAttr = '';
                if ("$value" == "$checked") {
                    $checkClass = ' active';
                    $checkAttr = ' checked="checked" ';
                }
                $temp .= '<label class="' . $colClass . $checkClass . '">
                                <input ' . $checkAttr . ' type="radio" name="' . $mains['name'] . '" class="' . $mains['class'] . '" value="' . $value . '" autocomplete="off" >' . $item . '
                        </label >';
            }
            $temp .= '</div>';
            $mains['value'] = $temp;
        }
        
        $this->form .= str_replace('#NAME#', $mains['name'], '<div class="form-group" id="' . $mains['id'] . '-div" >
                            <label class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <div class="row">' . $mains['value'] . '</div>
                                ' . $mains['info'] . '
                            </div>
                        </div>');
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addCheckBox($name, $options = []) {
        
        $mains = $this->main($name, $options);
        $mains['class'] = str_replace('form-control', '', $mains['class']);
        
        if (isset($options['list']) && ! empty($options['list'])) {
            $checked = $mains['value'];
            if ( ! is_array($checked)) {
                $checked = [$checked];
            }
            $mod = 12 % count($options['list']);
            $colClass = 'btn btn-default';
            if ($mod == 0) {
                $colClass .= ' col-xs-' . (12 / count($options['list']));
            }
            
            $temp = '<div class="btn-group col-xs-12" data-toggle="buttons">';
            foreach ($options['list'] as $value => $item) {
                
                $checkClass = '';
                $checkAttr = '';
                
                if (in_array($value, $checked)) {
                    $checkClass = ' active';
                    $checkAttr = ' checked="checked" ';
                }
                $temp .= '<label class="' . $colClass . $checkClass . '">
                                <input ' . $checkAttr . ' type="checkbox" name="' . $mains['name'] . '" class="' . $mains['class'] . '" value="' . $value . '" autocomplete="off" >' . $item . '
                        </label >';
            }
            $temp .= '</div>';
            $mains['value'] = $temp;
        }
        $this->form .= str_replace('#NAME#', $mains['name'] . '[]', '<div class="form-group" id="' . $mains['id'] . '-div" >
                            <label class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <div class="row">' . $mains['value'] . '</div>
                                ' . $mains['info'] . '
                            </div>
                        </div>');
        
        return $this;
    }
    
    /**
     * can have these extra options : info
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addSelect($name, $options = []) {
        
        SelectAsset::add();
        
        $mains = $this->main($name, $options);
        $prompt = '';
        if ( ! in_array('multiple', $options)) {
            if (isset($options['prompt'])) {
                if ($options['prompt'] != false) {
                    $prompt = '<option value="">' . $options['prompt'] . '</option>';
                }
                unset($options['prompt']);
            } else {
                $prompt = '<option value="">' . trans('words.choose') . '</option>';
            }
        }
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div" >
                            <label class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <select id="' . $mains['id'] . '" name="' . $mains['name'] . '" ' . $this->extra($mains, $options) . ' >
                                ' . $prompt . $mains['value'] . '
                                </select>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        return $this;
    }
    
    /**
     * can have these in options for ajax : url , multiple , cache , preserveSelected
     * can have these options for data-attributes in $option['data']
     *
     * @param string $name
     * @param array  $options
     *
     * @return $this
     */
    public function addAjaxSelect($name, $options = []) {
        
        //add the bootstrap-select asset
        SelectAsset::add();
        SelectAjaxAsset::add();
        
        $ajaxUrl = '';
        if (isset($options['url'])) {
            $ajaxUrl = $options['url'];
            unset($options['url']);
        }
        
        $preserveSelected = true;
        if (isset($options['preserveSelected'])) {
            $preserveSelected = (bool) $options['preserveSelected'];
            unset($options['preserveSelected']);
        }
        
        $cache = true;
        if (isset($options['cache'])) {
            $cache = (bool) $options['cache'];
            unset($options['cache']);
        }
        
        $mains = $this->main($name, $options);
        
        $prompt = '';
        if ( ! in_array('multiple', $options)) {
            if (isset($options['prompt'])) {
                if ($options['prompt'] != false) {
                    $prompt = '<option value="">' . $options['prompt'] . '</option>';
                }
                unset($options['prompt']);
            } else {
                $prompt = '<option value="">' . trans('words.choose') . '</option>';
            }
        }
        $this->form .= '<div class="form-group" id="' . $mains['id'] . '-div" >
                            <label class="' . $this->labelClass . ' control-label">' . $mains['title'] . '</label>
                            <div class="' . $this->holderClass . '">
                                <select data-live-search="true" id="' . $mains['id'] . '" name="' . $mains['name'] . '" ' . $this->extra($mains, $options) . ' >
                                ' . $prompt . $mains['value'] . '
                                </select>
                                ' . $mains['info'] . '
                                <ol class="help-block" id="' . $mains['id'] . '-msg"></ol>
                            </div>
                        </div>';
        
        Asset::addScript("
			$('#{$mains['id']}').selectpicker({
				liveSearch: true
			}).ajaxSelectPicker({
				langCode : 'fa',
				ajax: {
					url: '{$ajaxUrl}',
					type: 'get',
					dataType: 'json',
					data: function(){
						return {
							search : '{{{q}}}',
							exists : $('#{$mains['id']}').val(),
						};
					}
				},
				processData: function(data){
					return data;
				},
				cache : {$cache},
				preserveSelected: {$preserveSelected} ,
				ignoredKeys : {
					13 : 'enter'
				}
			});
		");
        
        return $this;
    }
    
    /**
     * @param string $text
     * @param string $small
     *
     * @return $this
     */
    public function addLegend($text, $small = null) {
        
        $this->form .= '<div class="form-group">
                            <div class="' . $this->labelClass . '" ></div>
                            <div class="' . $this->holderClass . '" >
                                <h4>' . $text . ' ' . (is_null($small) ? '' : '<small>' . $small . '</small>') . '</h4>
                            </div>
                            <div class="clearfix"></div>
                            <hr style="margin: 5px;" />
                        </div>';
        
        return $this;
    }
    
    /**
     * @param $html
     *
     * @return $this
     */
    public function addHtml($html) {
        
        $this->form .= $html;
        
        return $this;
    }
    
    /**
     * inserts a div with clearfix class to html
     */
    public function clearfix() {
        
        $this->form .= '<div class="clearfix"></div>';
    }
    
    /**
     * @internal param string $class
     * @internal param callable $data
     *
     * @return $this
     */
    public function box() {
        
        $args = func_get_args();
        
        $class = 'col-xs-12 col-sm-12 col-md-6';
        if (count($args) == 1) {
            $data = $args[0];
        } else {
            $class = $args[0];
            $data = $args[1];
        }
        
        //the callable cant be launched when added in the string
        $this->form .= '<div class="' . $class . '" >';
        $this->form .= $data($this, $this->model);
        $this->form .= '</div>';
        
        return $this;
    }
    
    /**
     * @param string|null $mainBtnTitle
     *
     * @param string|null $saveBtnTitle
     *
     * @return FormMaker $this
     */
    public function addSubmitButton($mainBtnTitle = null, $saveBtnTitle = null) {
        
        if ( ! $this->btnAdded) {
            $this->btnAdded = true;
            
            $saveBtn = '';
            $mainBtn = '<i class="fa fa-lg fa-save"></i> ذخیره';
            if ( ! empty($this->redirect)) {
                $saveBtn = '<button type="submit" name="stay" value="1" class="btn btn-info">
							<i class="fa fa-lg fa-save"></i> ذخیره و ماندن
						</button>';
            }
            
            if ( ! is_null($this->model)) {
                
                $mainBtn = '<i class="fa fa-lg fa-plus"></i> افزودن';
                if ( ! empty($this->redirect)) {
                    $saveBtn = '<button type="submit" name="stay" value="1" class="btn btn-info">
							<i class="fa fa-lg fa-plus"></i> افزودن و ماندن
						</button>';
                }
                
                if ($this->model->exists) {
                    
                    $mainBtn = '<i class="fa fa-lg fa-edit"></i> ویرایش';
                    if ( ! empty($this->redirect)) {
                        $saveBtn = '<button type="submit" name="stay" value="1" class="btn btn-info">
								<i class="fa fa-lg fa-edit"></i> ویرایش و ماندن
							</button>';
                    }
                }
                
            }
            
            if ( ! empty($mainBtnTitle)) {
                $mainBtn = $mainBtnTitle;
            }
            
            if ( ! empty($saveBtnTitle) && ! empty($saveBtn)) {
                $saveBtn = '<button type="submit" name="stay" value="1" class="btn btn-info">
								<i class="fa fa-lg fa-edit"></i> ' . $saveBtnTitle . '
							</button>';
            }
            
            $this->form .= '<br/>
			<div class="form-group">
				<div class="' . $this->labelClass . '"></div>
				<div class="' . $this->holderClass . '">
					<button type="submit" class="btn btn-success">' . $mainBtn . '</button>
					' . $this->redirect . $saveBtn . '
				</div>
			</div>';
            
        }
        
        return $this;
    }
    
    /**
     * @return string
     */
    public function render() {
        
        //adding the search class for integrating with ajaxForm
        $searchClass = '';
        if ($this->method == 'get') {
            $searchClass = 'searchForm';
        }
        
        //call to check the submit button added or not
        $this->addSubmitButton();
        
        return '<form 
				action="' . $this->url . '" 
				method="' . $this->method . '" 
				class="form-horizontal ' . $searchClass . '" 
				autocomplete="' . $this->autoComplete . '" 
				>' . $this->renderPartial(false) . '</form>';
        
    }
    
    /**
     * returns the html of form in raw format or not
     *
     * @param bool $raw
     *
     * @return string
     */
    public function renderPartial($raw = true) {
        
        return $this->form . ($raw ? '' : $this->methodField);
    }
    
    /**
     * @param bool $inArray
     *
     * @return FormMaker $this
     */
    public function namesInArray($inArray = true) {
        
        $this->namesInArray = false;
        if ($inArray) {
            $this->namesInArray = true;
            if (is_null($this->name)) {
                $this->name = static::$defaultInputName;
            }
        }
        
        return $this;
    }
    
    /**
     * @param string $url
     *
     * @return FormMaker $this
     */
    public function setUrl($url) {
        
        $this->url = $url;
        
        return $this;
    }
    
    /**
     * @param string $redirect
     *
     * @return FormMaker $this
     */
    public function setRedirect($redirect = null) {
        
        if ($redirect) {
            $this->redirect = '<input type="hidden" name="redirect" value="' . $redirect . '">';
        }
        
        return $this;
    }
    
    /**
     * @param Model|null $model
     *
     * @return FormMaker $this
     */
    public function setModel(Model $model = null) {
        
        $this->model = $model;
        if ($model) {
            $this->setName($model->getTable());
        }
        
        return $this;
    }
    
    /**
     * @param string $name
     *
     * @return FormMaker $this
     */
    public function setName($name) {
        
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * @param string|null $method
     *
     * @return FormMaker $this
     */
    public function setMethod($method = null) {
        
        if (is_null($method)) {
            $method = 'POST';
        }
        
        if ( ! preg_grep("/{$method}/i", static::$verbs)) {
            $method = 'POST';
        }
        
        $this->method = $method;
        if ( ! preg_grep("/{$this->method}/i", ['get', 'post'])) {
            $this->methodField = '<input type="hidden" name="_method" value="' . $this->method . '">';
            $this->method = 'POST';
        }
        
        return $this;
    }
    
    /**
     * @param int $labelSize
     * @param int $holderSize
     *
     * @return FormMaker $this
     *
     */
    public function resetColSizeRatio($labelSize = 2, $holderSize = 0) {
        
        if ($labelSize < 1) {
            $labelSize = 1;
        } elseif ($labelSize > 12) {
            $labelSize = 12;
        }
        
        $remainSize = 12 - $labelSize;
        
        if ( ! (1 <= $holderSize && $holderSize <= $remainSize)) {
            $holderSize = $remainSize;
        }
        
        $this->labelClass = 'col-xs-' . $labelSize;
        $this->holderClass = 'col-xs-' . $holderSize;
        
        return $this;
    }
    
    /**
     * return the class for labels
     * @return string
     */
    public function getLabelClass() {
        
        return $this->labelClass;
    }
    
    /**
     * returns the class for holder divs
     * @return string
     */
    public function getHolderClass() {
        
        return $this->holderClass;
    }
    
    /**
     * @param boolean $on
     *
     * @return FormMaker $this
     */
    public function autoComplete($on = true) {
        
        $this->autoComplete = $on ? 'on' : 'off';
        
        return $this;
    }
    
    /**
     * the prefix for id attributes of elements
     *
     * @param string $prefix
     *
     * @return FormMaker $this
     */
    public function setIdPrefix($prefix) {
        
        $this->idPrefix = $prefix;
        
        return $this;
    }
    
    /**
     * the prefix for class attributes of elements
     *
     * @param string $prefix
     *
     * @return FormMaker $this
     */
    public function setClassPrefix($prefix) {
        
        $this->classPrefix = $prefix;
        
        return $this;
    }
    
    /**
     * returns the 'name','multiple','id','class','title','value' & info
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return array
     */
    private function main($name, array $attributes = []) {
        
        //name attribute
        $mains['name'] = $name;
        if ($this->namesInArray) {
            $mains['name'] = $this->name . '[' . $name . ']';
        }
        
        //multiple attribute
        if (array_key_exists('multiple', $attributes)) {
            $mains['name'] .= '[]';
        }
        
        //id attribute
        $mains['id'] = $name;
        if (array_key_exists('id', $attributes)) {
            $mains['id'] = $attributes['id'];
        }
        if ($this->idPrefix && ! empty($mains['id'])) {
            $mains['id'] = $this->idPrefix . '-' . $mains['id'];
        }
        
        //class attribute
        $mains['class'] = $name;
        if (array_key_exists('class', $attributes)) {
            $mains['class'] = $attributes['class'];
        }
        if ($this->classPrefix && ! empty($mains['class'])) {
            $mains['class'] = $this->classPrefix . '-' . $mains['class'];
        }
        $mains['class'] .= ' form-control';
        
        //title attribute
        $mains['title'] = trans('validation.attributes.' . $name);
        if (isset($attributes['title'])) {
            $mains['title'] = $attributes['title'];
        }
        
        //info in .help-block element
        $mains['info'] = '';
        if (isset($attributes['info'])) {
            $mains['info'] = '<p class="help-block">' . $attributes['info'] . '</p>';
        }
        
        //value attribute + default
        $mains['value'] = isset($attributes['default']) ? $attributes['default'] : '';
        
        if (isset($attributes['value'])) {
            
            if (is_callable($attributes['value'])) {
                $mains['value'] = $attributes['value']($this->model->getAttribute($name), $this->model);
            } else {
                $mains['value'] = $attributes['value'];
            }
            
        } else {
            if ( ! is_null($this->model)) {
                $mains['value'] = $this->model->getAttribute($name);
            }
        }
        
        return $mains;
    }
    
    /**
     * return other attributes
     *
     * @param array $mains
     * @param array $options
     *
     * @return string
     */
    private function extra(array $mains, array $options = []) {
        
        unset($options['value']);
        $remains = array_diff($options, $mains);
        $remains['class'] = $mains['class'];
        
        $extra = [];
        foreach ($remains as $key => $value) {
            if (is_numeric($key)) {
                $extra[] = $value;
            } else {
                $extra[] = $key . '="' . $value . '"';
            }
        }
        
        return implode(' ', $extra);
    }
    
}
