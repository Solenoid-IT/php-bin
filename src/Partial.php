<?php



namespace Solenoid\BIN;



class Partial
{
    public int $loaded;
    public int $total;



    # Returns [self]
    public function __construct (int $loaded, int $total)
    {
        // (Getting the values)
        $this->loaded = $loaded;
        $this->total  = $total;
    }

    # Returns [Partial]
    public static function create (int $loaded, int $total)
    {
        // Returning the value
        return new Partial( $loaded, $total );
    }



    # Returns [assoc]
    public function to_array ()
    {
        // Returning the value
        return get_object_vars( $this );
    }
}



?>