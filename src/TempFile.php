<?php



namespace Solenoid\BIN;



use \Solenoid\System\File;
use \Solenoid\System\Stream;

use \Solenoid\BIN\Partial;



class TempFile
{
    public string   $path;

    public string   $name;
    public string   $type;

    public ?Partial $partial;



    private Stream  $stream;



    # Returns [self]
    public function __construct (string $path, string $name, string $type, ?Partial $partial = null )
    {
        // (Getting the values)
        $this->path    = $path;

        $this->name    = $name;
        $this->type    = $type;

        $this->partial = $partial;
    }

    # Returns [TempFile]
    public static function create (string $path, string $name, string $type, ?Partial $partial = null)
    {
        // Returning the value
        return new TempFile( $path, $name, $type, $partial );
    }



    # Returns [bool] | Throws [Exception]
    public function move (string $dst_path)
    {
        if ( File::select( $this->path )->move( $dst_path ) === false )
        {// (Unable to move the file)
            // (Setting the value)
            $message = "Unable to move the file";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // Returning the value
        return true;
    }



    # Returns [Stream|false] | Throws [Exception]
    public function open ()
    {
        // (Getting the value)
        $input_stream = Stream::open( $this->path );

        if ( $input_stream === false )
        {// (Unable to open the stream)
            // (Setting the value)
            $message = "Unable to open the stream";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Getting the value)
        $this->stream = &$input_stream;



        // Returning the value
        return $input_stream;
    }



    # Returns [assoc]
    public function to_array ()
    {
        // Returning the value
        return
            [
                'path'    => $this->path,

                'name'    => $this->name,
                'type'    => $this->type,

                'partial' => $this->partial ? $this->partial->to_array() : null
            ]
        ;
    }



    # Returns [string]
    public function __toString ()
    {
        // Returning the value
        return $this->path;        
    }



    # Returns [void] | Throws [Exception]
    public function __destruct ()
    {
        if ( isset( $this->stream ) )
        {// Value found
            if ( !$this->stream->close() )
            {// (Unable to close the stream)
                // (Setting the value)
                $message = "Unable to close the stream";

                // Throwing an exception
                throw new \Exception($message);

                // Returning the value
                return;
            }
        }
    }
}



?>