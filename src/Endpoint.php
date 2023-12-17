<?php



namespace Solenoid\BIN;



use \Solenoid\HTTP\Request;
use \Solenoid\HTTP\Cookie;

use \Solenoid\System\Resource;
use \Solenoid\System\Directory;
use \Solenoid\System\File;

use \Solenoid\BIN\TempFile;
use \Solenoid\BIN\Partial;



class Endpoint
{
    private array  $handlers;
    private string $basedir;
    private Cookie $cookie;

    private string $transfer_id;
    private array  $input;

    private string $state;

    private ?array $data;
    private array  $files;



    # Returns [self]
    public function __construct (array $handlers, string $basedir, Cookie $cookie)
    {
        // (Getting the values)
        $this->handlers = $handlers;
        $this->basedir  = $basedir;
        $this->cookie   = $cookie;



        // (Setting the values)
        $this->data  = [];
        $this->files = [];
    }

    # Returns [Endpoint]
    public static function create (array $handlers, string $basedir, Cookie $cookie)
    {
        // Returning the value
        return new Endpoint( $handlers, $basedir, $cookie );
    }



    # Returns [bool] | Throws [Exception]
    public function start ()
    {
        // (Reading the request)
        $request = Request::read();

        if ( $request::$method !== 'BIN' )
        {// Match failed
            // (Setting the value)
            $message = "BIN :: Request method is not valid";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        if ( !isset( $request::$headers['Action'] ) )
        {// Value not found
            // (Setting the value)
            $message = "BIN :: Action not found";

            // Throwing an exception
            throw new \Exception($message);

            // Returning the value
            return false;
        }



        // (Getting the value)
        list( $subject, $verb ) = explode( '::', $request::$headers['Action'] );

        switch ( $subject )
        {
            case 'transfer':
                switch ( $verb )
                {
                    case 'open':
                        // (Getting the value)
                        $this->transfer_id = $this->handlers[ 'generate_id' ]();

                        if ( !$this->cookie->set( $this->transfer_id ) )
                        {// (Unable to set the cookie)
                            // (Setting the value)
                            $message = "Unable to set the cookie";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }



                        if ( Directory::select( "$this->basedir/$this->transfer_id" )->make() === false )
                        {// (Unable to make the directory)
                            // (Setting the value)
                            $message = "Unable to make the directory";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }

                        if ( File::select( "$this->basedir/$this->transfer_id/input" )->write( $request::$body ) === false )
                        {// (Unable to write the content to the file)
                            // (Setting the value)
                            $message = "Unable to write the content to the file";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }
                    break;

                    case 'close':
                        // (Getting the value)
                        $this->transfer_id = Cookie::fetch_value( $this->cookie->name );

                        if ( !$this->handlers[ 'validate_id' ]( $this->transfer_id ) )
                        {// (Validation failed)
                            // Returning the value
                            return false;
                        }



                        if ( !Cookie::delete( $this->cookie->name, $this->cookie->domain, $this->cookie->path ) )
                        {// (Unable to delete the cookie)
                            // (Setting the value)
                            $message = "Unable to delete the cookie";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }



                        // (Getting the value)
                        $result = json_decode( $request::$body, true );



                        // (Getting the value)
                        $this->state = $result['state'];



                        // (Getting the value)
                        $input_content = File::select( "$this->basedir/$this->transfer_id/input" )->read();

                        if ( $input_content === false )
                        {// (Unable to read the content from the file)
                            // (Setting the value)
                            $message = "Unable to read the content from the file";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }



                        // (Getting the value)
                        $this->input = json_decode( $input_content, true );



                        // (Getting the value)
                        $this->data = $this->input['data'];



                        // (Getting the value)
                        $files = array_filter( Directory::select( "$this->basedir/$this->transfer_id" )->list( 1 ), function ($resource) { return Resource::select( $resource )->is_dir(); } );

                        foreach ($files as $file_path)
                        {// Processing each entry
                            // (Getting the value)
                            $file_id = (int) basename( $file_path );



                            if ( File::select( "$file_path.merge" )->write() === false )
                            {// (Unable to write the content to the file)
                                // (Setting the value)
                                $message = "Unable to write the content to the file";

                                // Throwing an exception
                                throw new \Exception($message);

                                // Returning the value
                                return false;
                            }



                            // (Getting the value)
                            $file_chunks = array_filter( Directory::select( $file_path )->list( 1 ), function ($resource) { return Resource::select( $resource )->is_file(); } );

                            foreach ($file_chunks as $file_chunk_path)
                            {// Processing each entry
                                // (Getting the value)
                                $file_chunk_id = basename( $file_chunk_path );



                                // (Getting the value)
                                $file_chunk_content = File::select( $file_chunk_path )->read();

                                if ( $file_chunk_content === false )
                                {// (Unable to read the content from the file)
                                    // (Setting the value)
                                    $message = "Unable to read the content from the file";

                                    // Throwing an exception
                                    throw new \Exception($message);

                                    // Returning the value
                                    return false;
                                }



                                if ( File::select( "$file_path.merge" )->write( $file_chunk_content, 'append' ) === false )
                                {// (Unable to write the content to the file)
                                    // (Setting the value)
                                    $message = "Unable to write the content to the file";
    
                                    // Throwing an exception
                                    throw new \Exception($message);
    
                                    // Returning the value
                                    return false;
                                }
                            }



                            if ( Directory::select( $file_path )->remove() === false )
                            {// (Unable to remove the directory)
                                // (Setting the value)
                                $message = "Unable to remove the directory";

                                // Throwing an exception
                                throw new \Exception($message);

                                // Returning the value
                                return false;
                            }



                            if ( File::select( "$file_path.merge" )->move( "$file_path" ) === false )
                            {// (Unable to move the file)
                                // (Setting the value)
                                $message = "Unable to move the file";

                                // Throwing an exception
                                throw new \Exception($message);

                                // Returning the value
                                return false;
                            }



                            // (Getting the values)
                            $file_name = $this->input['files'][ $file_id - 1 ]['name'];
                            $file_type = $this->input['files'][ $file_id - 1 ]['type'];



                            // (Getting the value)
                            $partial = $result['partials'][ $file_id - 1 ] ?? null;
                            $partial = $partial ? Partial::create( $partial['loaded'], $partial['total'] ) : null;



                            // (Appending the value)
                            $this->files[] = TempFile::create( $file_path, $file_name, $file_type, $partial );
                        }



                        // (Calling the function)
                        $this->handlers[ 'ended' ]( Transfer::create( $this->state, $this->data, $this->files ) );



                        if ( Directory::select( "$this->basedir/$this->transfer_id" )->remove() === false )
                        {// (Unable to remove the directory)
                            // (Setting the value)
                            $message = "Unable to remove the directory";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }
                    break;

                    default:
                        // (Setting the value)
                        $message = "BIN :: Action not recognized";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return false;
                }
            break;

            case 'chunk':
                switch ( $verb )
                {
                    case 'store':
                        // (Getting the value)
                        $this->transfer_id = Cookie::fetch_value( $this->cookie->name );

                        if ( !$this->handlers[ 'validate_id' ]( $this->transfer_id ) )
                        {// (Validation failed)
                            // Returning the value
                            return false;
                        }



                        // (Getting the values)
                        $file_id       = (int) $request::$headers['File-Id'];
                        $file_chunk_id = (int) $request::$headers['File-Chunk-Id'];

                        $file_chunk_path = "$this->basedir/$this->transfer_id/$file_id/$file_chunk_id";

                        if ( File::select( $file_chunk_path )->write( $request::$body ) === false )
                        {// (Unable to write the content to the file)
                            // (Setting the value)
                            $message = "Unable to write the content to the file";

                            // Throwing an exception
                            throw new \Exception($message);

                            // Returning the value
                            return false;
                        }
                    break;

                    default:
                        // (Setting the value)
                        $message = "BIN :: Action not recognized";

                        // Throwing an exception
                        throw new \Exception($message);

                        // Returning the value
                        return false;
                }
            break;
        }



        // Returning the value
        return true;
    }
}



?>