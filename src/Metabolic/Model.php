<?php

namespace Metabolic;

class Model extends Base
{
  function __construct($post_obj)
  {
    if(is_numeric($post_obj))
    {
      $post_obj = get_post($post_obj);
    }
    $this->ID = $post_obj->ID;
    $this->obj = $post_obj;
  }
  
  function __get($name)
  {
    if(!$this->obj) $this->obj = get_post($id);
    if(isset($this->obj->$name)) return $this->obj->$name;
    return $this->$name = get_post_meta($this->ID, $name, true);
  }
  
  function title()
  {
    return sprintf("%s", $this->name);
  }
  
  function update($args)
  {
    foreach($args as $k=>$v)
    {
      $this->$k = $v;
    }
    $this->save();
  }
  
  function save()
  {
    wp_update_post(array(
      'ID'=>$this->obj->ID,
      'post_title'=>$this->title(),
    ));
    foreach($this->fields as $k)
    {
      if(!isset($this->$k)) continue;
      update_post_meta($this->ID, $k, $this->$k);
    }
  }
}