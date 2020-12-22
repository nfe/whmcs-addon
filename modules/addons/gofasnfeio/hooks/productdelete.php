<?php
logModuleCall('gofas_nfeio', 'vars prod delete',$vars , '',  '', 'replaceVars');
Capsule::table('tblproductcode')->where('product_id','=',$vars['pid'])->delete();