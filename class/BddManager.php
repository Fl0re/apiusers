<?php 
class BddManager {

    private $connection;
    private $userRepository;

    /**
     * BddManager constructor.
     */
    public function __construct(){
        $this->connection = Connection::getConnection();
        $this->userRepository = new UserRepository( $this->connection );
    }

 

    /**
     * @return UserRepository
     */
    public function getUserRepository() {
        return $this->userRepository;
    }

}