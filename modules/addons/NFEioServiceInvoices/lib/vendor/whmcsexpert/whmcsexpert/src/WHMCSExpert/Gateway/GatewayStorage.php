<?php

namespace WHMCSExpert\Gateway;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Arr;

class GatewayStorage extends Fo
{
    /**
     * @var AbstractModule
     */
    protected $module;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Whether data loaded or not
     *
     * @var bool
     */
    protected $dataLoaded = false;

    /**
     * Keys with updated data
     *
     * @var array
     */
    protected $dataUpdate = [];

    /**
     * Keys with removed data
     *
     * @var array
     */
    protected $dataRemove = [];

    protected $storageKey;

    protected $gateway;

    public function __construct()
    {
        // $this->storageKey = self::STORAGE_KEY;
        $this->module = self::SYSTEM_NAME;
        $this->loadData();
    }

    /**
     * Seta a chave de storage do gateway (seu nome) para acessar seus atributos
     * no banco de dados
     * @param string $gateway nome do gateway de pagamento
     */
    public function setGateway($gateway)
    {
        $this->storageKey = $gateway;
    }

    /**
     * Get data by key (can be path divided by dots - `.`)
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $this->loadData();

        return Arr::get($this->data, $key);
    }

    public function getAll()
    {
        $this->loadData();

        return $this->data;
    }

    /**
     * Set data to storage
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function set($key, $value)
    {
        Arr::set($this->data, $key, $value);
        $this->dataUpdate[] = $this->splitKeyPath($key)[0];

        $this->persistData();

        return $this;
    }

    /**
     * Is there data in storage
     *
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        $this->loadData();

        return Arr::has($this->data, $key);
    }

    /**
     * Remove data from storage
     * @param $key
     * @return $this
     */
    public function remove($key)
    {
        // Arrays::forget($this->data, $key);
        $this->dataRemove[] = $this->splitKeyPath($key)[0];

        $this->persistData();

        return $this;
    }

    // --- Internal

    protected function loadData()
    {
        // Nothing to process
        if ($this->dataLoaded) {
            return $this;
        }

        $data = Capsule::table('tblpaymentgateways')->select('setting', 'value')->where('gateway', $this->getStorageKey())->get();

        foreach ($data as $row) {
            $key = $row->setting;
            $value = $row->value;

            // try {
            //     $value = @json_decode($value);
            // } catch (\Exception $e) {
            //     continue;
            // }

            $this->data[ $key ] = $value;
        }

        // $this->dataLoaded = true;

        return $this;
    }

    protected function persistData()
    {
        // Nothing to update
        // if (empty($this->dataUpdate) || empty($this->dataRemove)) {
        //     return $this;
        // }

        // Skip any duplicates
        $this->dataUpdate = array_unique($this->dataUpdate);
        $this->dataRemove = array_unique($this->dataRemove);

        $storageKey = $this->getStorageKey();

        foreach ($this->dataUpdate as $mainKey) {
            if (isset($this->data[$mainKey])) {
                $data = $this->data[$mainKey];
                $row = Capsule::table('tblpaymentgateways')->select('id')->where(['gateway' => $storageKey, 'setting' => $mainKey])->first();
                // Determine method
                if (!empty($row->id)) {
                    // Insert
                    Capsule::table('tblpaymentgateways')->where('id', $row->id)->update(['value' => $data]);
                } else {
                    // Update
                    Capsule::table('tblpaymentgateways')->insert(['gateway' => $storageKey, 'setting' => $mainKey, 'value' => $data]);
                }

                unset($this->data[$mainKey]);
            }
        }
        $this->dataUpdate = [];

        foreach ($this->dataRemove as $mainKey) {
            if (isset($this->data[$mainKey])) {
                Capsule::table('tblpaymentgateways')->where([['gateway', $storageKey], ['setting', $mainKey]])->delete();

                unset($this->data[$mainKey]);
            }
        }

        $this->dataRemove = [];

        return $this;
    }

    // --- Helpers

    /**
     * Get scope for db storage
     *
     * @return string
     */
    protected function getStorageKey()
    {
        return $this->storageKey;
    }

    /**
     * Get ["main", "sub.path"] from "main.sub.path" string
     * @param $key
     * @return array
     */
    protected function splitKeyPath($key)
    {
        // It's already main key with subpath items
        if (false === strpos($key, '.')) {
            return [$key, null];
        }

        $key = explode('.', $key);

        return [$key[ 0 ], join('.', array_slice($key, 1))];
    }
}
