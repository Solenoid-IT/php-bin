<?php



namespace Solenoid\BIN;



class Transfer
{
    public string $state;

    public ?array $data;
    public array  $files;



    # Returns [self]
    public function __construct (string $state, ?array $data, array $files)
    {
        // (Getting the values)
        $this->state = $state;

        $this->data  = $data;
        $this->files = $files;
    }

    # Returns [Transfer]
    public static function create (string $state, ?array $data, array $files)
    {
        // Returning the value
        return new Transfer( $state, $data, $files );
    }



    # Returns [string]
    public function __toString ()
    {
        // (Setting the value)
        $result = [];



        // (Getting the values)
        $result['state'] = $this->state;
        $result['data']  = $this->data;

        foreach ($this->files as $temp_file)
        {// Processing each entry
            // (Appending the value)
            $result['files'][] = $temp_file->to_array();
        }



        // Returning the value
        return json_encode( $result );
    }
}



?>