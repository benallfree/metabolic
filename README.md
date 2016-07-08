# metabolic

Metabolic is an ORM for WordPress based on custom post types and postmeta fields. It supports migrations,
inheritance, compex data types, and GUI controls.

## Installation

Metabolic is designed to be included *inside* your plugin:

    composer require benallfree/metabolic

or, for the latest,

    composer require benallfree/metabolic dev-master

## Quickstart

Metabolic works with Custom Post Types and (User) by defining custom post types with no default exposure to the UI.

Let's suppose we want to create an Order model.

    class Order extends Metabolic\Base
    {
    }
      

Bang. Done. An 'order' custom post type now exists. But, it has no fields.
That's not very useful. Let's create some by overriding the `fields()` function.


    class Order extends Metabolic\Base
    {
      static function fields()
      {
        return array(
          'cust_id',
          'status',
        );
      }
    }
        
That's a little better. Now we can do this:

    $order = new Order();
    $order->cust_id = 42;
    $order->status = 'New';
    $post_id = $order->save();

Pretty cool. But still not great, where's the validation?

    class Order extends Metabolic\Base
    {
      static function fields()
      {
        return array(
          'cust_id'=>array('required'=>true),
          'status'=>array('required'=>true),
        );
      }
    }

Now if we attempt to `save()` an invalid model, it will fail.

    $order = new Order();
    $order->cust_id = 42;
    // $order->status = 'New';
    $post_id = $order->save();
    var_dump($order->errors);

Nice. Or, we can do the validation ourselves, like this:

    class Order extends PostRecordBase
    {
      static function fields()
      {
        return array(
          'cust_id',
          'status',
        );
      }
    }
    
    add_action('wp_model_order_validate', function($obj) {
      if(!$obj->cust_id)
      {
        $obj->errors[] = new RecordError("is required", 'cust_id');
      }
      if(!$obj->status)
      {
        $obj->errors[] = new RecordError("is required", 'status');
      } else {
        if(!array_search($obj->status, array('New', 'Hold', 'Completed', 'Canceled'))
        {
          $obj->errors[] = new RecordError("'{$obj->status}' is not allowed", 'status');
        }
      }
    });

Or, a little cleaner (to me), like this:

    class Order extends PostRecordBase
    {
      static function fields()
      {
        return array(
          'cust_id',
          'status',
        );
      }
      
      static function actions()
      {
        return array(
          'validate'=>function($obj) {
            if(!$obj->cust_id)
            {
              $obj->errors[] = new RecordError("is required", 'cust_id');
            }
            if(!$obj->status)
            {
              $obj->errors[] = new RecordError("is required", 'status');
            } else {
              if(!array_search($obj->status, array('New', 'Hold', 'Completed', 'Canceled')))
              {
                $obj->errors[] = new RecordError("'{$obj->status}' is not allowed", 'status');
              }
            }
          }
        );
      }
    }

Uh, but wait, shouldn't statuses default to New?

    class Order extends PostRecordBase
    {
      static function fields()
      {
        return array(
          'cust_id'=>array('required'=>true),
          'status'=>array('default'=>'New', 'required'=>true),
        );
      }
    }

Well that was easy. But now it's a month later and I want to add a field. No problem. Just add it. No migrations needed.

    class Order extends PostRecordBase
    {
      static function fields()
      {
        return array(
          'cust_id'=>array('required'=>true),
          'status'=>array('default'=>'New', 'required'=>true),
          'shipping_notes',
        );
      }
    }

Now I have a problem though. I want to date stamp when the order arrived at the customer's location, but only going forward.
I don't want to deal with all the old records, I just want to assume the orders arrived at the last time the record was updated.
Metabolic keeps track of the record version and runs migrations *on a per record basis, as needed*. That means no external scripts.
Migrating is easy:

    class Order extends PostRecordBase
    {
      static function fields()
      {
        return array(
          'cust_id'=>array('required'=>true),
          'status'=>array('default'=>'New', 'required'=>true),
          'shipping_notes',
          'order_arrived_at',
        );
      }
    
      static function migrations() {
        return array(
          function($obj) {
            if(!$obj->order_arrived_at)
            {
              $obj->order_arrived_at = $obj->updated_at;
            }
          }
        );
      }
    }




## Finding records

It's easy to find a single record by post ID.

    $order = Order::find(42);

It's easy to find a single record by post object.

    $order = Order::find($my_post_obj);

It's easy to find a single record by meta value.

    $order = Order::find( array(
      'customer_id'=>42,
    ));
    
It's also easy to find a bunch of records.

    $orders = Order::find_all( array(
      'status'=>'New',
    ));

In fact, you can do any kind of query you want because you are really passing the [meta_query array](http://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters).
If you don't know what that is, you can [read here](http://scribu.net/wordpress/advanced-metadata-queries.html) too.

## Relationships

Let's say a customer has orders.

    class Customer extends UserRecordBase
    {
      static function relations()
      {
        return array(
          'orders'=>array('type'=>'has_many', 'key'=>'customer_id', 'class'=>'Order'),
        );
      }
    }
    
    $customer = Customer::find_by_ID(42);
    var_dump($customer->orders);
  
## Hook reference

`<type>` will 'tableize' the class name. Order because type `order`. CustomerAddress becomes `customer_address`

    wp_models_<type>_before_load
    wp_models_<type>_after_load
    wp_models_<type>_before_save
    wp_models_<type>_after_save
    wp_models_<type>_before_validate
    wp_models_<type>_validate
    wp_models_<type>_after_validate
