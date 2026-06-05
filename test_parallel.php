<?php
sleep(2); // Имитируем обработку
echo "Processed by PID: " . getmypid() . " at " . date('H:i:s') . "\n";
