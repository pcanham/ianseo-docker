<?php

// categories and their equivalents for record purposes
// Divisions we have are R, C, B, W1, VI


$ClEquivSQL="INSERT IGNORE INTO `ClassWaEquivalents` (`ClWaEqTourRule`, `ClWaEqFrom`, `ClWaEqTo`, `ClWaEqEvent`, `ClWaEqGender`, `ClWaEqDivision`, `ClWaEqAgeClass`, `ClWaEqMain`, `ClWaEqTeam`, `ClWaEqMixedTeam`, `ClWaEqPara`, `ClWaEqComponents`) VALUES
('WA', 21, 49, 'RM', 0, 'R', 'M', 1, 3, 0, 0, 3),
('WA', 18, 20, 'RM', 0, 'R', 'JM', 0, 3, 0, 0, 3),
('WA', 0, 17, 'RM', 0, 'R', 'CM', 0, 3, 0, 0, 3),
('WA', 50, 100, 'RM', 0, 'R', 'MM', 0, 3, 0, 0, 3),
('WA', 21, 49, 'RW', 1, 'R', 'W', 1, 3, 0, 0, 3),
('WA', 18, 20, 'RW', 1, 'R', 'JW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'RW', 1, 'R', 'CW', 0, 3, 0, 0, 3),
('WA', 50, 100, 'RW', 1, 'R', 'MW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'RJM', 0, 'R', 'CM', 0, 3, 0, 0, 3),
('WA', 18, 20, 'RJM', 0, 'R', 'JM', 1, 3, 0, 0, 3),
('WA', 18, 20, 'RJW', 1, 'R', 'JW', 1, 3, 0, 0, 3),
('WA', 0, 17, 'RJW', 1, 'R', 'CW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'RCM', 0, 'R', 'CM', 1, 3, 0, 0, 3),
('WA', 0, 17, 'RCW', 1, 'R', 'CW', 1, 3, 0, 0, 3),
('WA', 50, 100, 'RMM', 0, 'R', 'MM', 1, 3, 0, 0, 3),
('WA', 50, 100, 'RMW', 1, 'R', 'MW', 1, 3, 0, 0, 3),
('WA', 21, 49, 'CM', 0, 'C', 'M', 1, 3, 0, 0, 3),
('WA', 18, 20, 'CM', 0, 'C', 'JM', 0, 3, 0, 0, 3),
('WA', 0, 17, 'CM', 0, 'C', 'CM', 0, 3, 0, 0, 3),
('WA', 50, 100, 'CM', 0, 'C', 'MM', 0, 3, 0, 0, 3),
('WA', 21, 49, 'CW', 1, 'C', 'W', 1, 3, 0, 0, 3),
('WA', 18, 20, 'CW', 1, 'C', 'JW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'CW', 1, 'C', 'CW', 0, 3, 0, 0, 3),
('WA', 50, 100, 'CW', 1, 'C', 'MW', 0, 3, 0, 0, 3),
('WA', 18, 20, 'CJM', 0, 'C', 'JM', 1, 3, 0, 0, 3),
('WA', 0, 17, 'CJM', 0, 'C', 'CM', 0, 3, 0, 0, 3),
('WA', 18, 20, 'CJW', 1, 'C', 'JW', 1, 3, 0, 0, 3),
('WA', 0, 17, 'CJW', 1, 'C', 'CW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'CCM', 0, 'C', 'CM', 1, 3, 0, 0, 3),
('WA', 0, 17, 'CCW', 1, 'C', 'CW', 1, 3, 0, 0, 3),
('WA', 50, 100, 'CMM', 0, 'C', 'MM', 1, 3, 0, 0, 3),
('WA', 50, 100, 'CMW', 1, 'C', 'MW', 1, 3, 0, 0, 3),
('WA', 21, 49, 'BM', 0, 'B', 'M', 1, 3, 0, 0, 3),
('WA', 18, 20, 'BM', 0, 'B', 'JM', 0, 3, 0, 0, 3),
('WA', 0, 17, 'BM', 0, 'B', 'CM', 0, 3, 0, 0, 3),
('WA', 50, 100, 'BM', 0, 'B', 'MM', 0, 3, 0, 0, 3),
('WA', 21, 49, 'BW', 1, 'B', 'W', 1, 3, 0, 0, 3),
('WA', 18, 20, 'BW', 1, 'B', 'JW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'BW', 1, 'B', 'CW', 0, 3, 0, 0, 3),
('WA', 50, 100, 'BW', 1, 'B', 'MW', 0, 3, 0, 0, 3),
('WA', 18, 20, 'BJM', 0, 'B', 'JM', 1, 3, 0, 0, 3),
('WA', 0, 17, 'BJM', 0, 'B', 'CM', 0, 3, 0, 0, 3),
('WA', 18, 20, 'BJW', 1, 'B', 'JW', 1, 3, 0, 0, 3),
('WA', 0, 17, 'BJW', 1, 'B', 'CW', 0, 3, 0, 0, 3),
('WA', 0, 17, 'BCM', 0, 'B', 'CM', 1, 3, 0, 0, 3),
('WA', 0, 17, 'BCW', 1, 'B', 'CW', 1, 3, 0, 0, 3),
('WA', 50, 100, 'BMM', 0, 'B', 'MM', 1, 3, 0, 0, 3),
('WA', 50, 100, 'BMW', 1, 'B', 'MW', 1, 3, 0, 0, 3),
('WA', 0, 100, 'VI1', -1, 'VI', '1', 1, 1, 0, 1, 1),
('WA', 0, 100, 'VI23', -1, 'VI', '23', 1, 1, 0, 1, 1),
('WA', 18, 20, 'CX', 1, 'C', 'JW', 0, 2, 1, 0, 2),
('WA', 0, 17, 'CX', 0, 'C', 'CM', 0, 2, 1, 0, 2),
('WA', 21, 49, 'CX', 0, 'C', 'M', 1, 2, 1, 0, 2),
('WA', 0, 17, 'CX', 1, 'C', 'CW', 0, 2, 1, 0, 2),
('WA', 21, 49, 'CX', 1, 'C', 'W', 1, 2, 1, 0, 2),
('WA', 50, 100, 'CX', 0, 'C', 'MM', 0, 2, 1, 0, 2),
('WA', 18, 20, 'CX', 0, 'C', 'JM', 0, 2, 1, 0, 2),
('WA', 50, 100, 'CX', 1, 'C', 'MW', 0, 2, 1, 0, 2),
('WA', 50, 100, 'RX', 0, 'R', 'MM', 0, 2, 1, 0, 2),
('WA', 18, 20, 'RX', 0, 'R', 'JM', 0, 2, 1, 0, 2),
('WA', 50, 100, 'RX', 1, 'R', 'MW', 0, 2, 1, 0, 2),
('WA', 18, 20, 'RX', 1, 'R', 'JW', 0, 2, 1, 0, 2),
('WA', 0, 17, 'RX', 0, 'R', 'CM', 0, 2, 1, 0, 2),
('WA', 21, 49, 'RX', 0, 'R', 'M', 1, 2, 1, 0, 2),
('WA', 0, 17, 'RX', 1, 'R', 'CW', 0, 2, 1, 0, 2),
('WA', 21, 49, 'RX', 1, 'R', 'W', 1, 2, 1, 0, 2),
('WA', 0, 17, 'CCX', 0, 'C', 'CM', 1, 2, 1, 0, 2),
('WA', 0, 17, 'CCX', 1, 'C', 'CW', 1, 2, 1, 0, 2),
('WA', 0, 100, 'RXO', 0, 'R', 'M', 1, 2, 1, 1, 2),
('WA', 0, 100, 'RXO', 1, 'R', 'W', 1, 2, 1, 1, 2),
('WA', 0, 17, 'RJX', 1, 'R', 'CW', 0, 2, 1, 0, 2),
('WA', 18, 20, 'RJX', 0, 'R', 'JM', 1, 2, 1, 0, 2),
('WA', 18, 20, 'RJX', 1, 'R', 'JW', 1, 2, 1, 0, 2),
('WA', 0, 17, 'RJX', 0, 'R', 'CM', 0, 2, 1, 0, 2),
('WA', 0, 17, 'RCX', 0, 'R', 'CM', 1, 2, 1, 0, 2),
('WA', 0, 17, 'RCX', 1, 'R', 'CW', 1, 2, 1, 0, 2),
('WA', 0, 100, 'CXO', 0, 'C', 'M', 1, 2, 1, 1, 2),
('WA', 0, 100, 'CXO', 1, 'C', 'W', 1, 2, 1, 1, 2),
('WA', 0, 17, 'CJX', 0, 'C', 'CM', 0, 2, 1, 0, 2),
('WA', 0, 17, 'CJX', 1, 'C', 'CW', 0, 2, 1, 0, 2),
('WA', 18, 20, 'CJX', 0, 'C', 'JM', 1, 2, 1, 0, 2),
('WA', 18, 20, 'CJX', 1, 'C', 'JW', 1, 2, 1, 0, 2),
('WA', 21, 49, 'BX', 1, 'B', 'W', 1, 2, 1, 0, 2),
('WA', 50, 100, 'BX', 0, 'B', 'MM', 0, 2, 1, 0, 2),
('WA', 18, 20, 'BX', 0, 'B', 'JM', 0, 2, 1, 0, 2),
('WA', 50, 100, 'BX', 1, 'B', 'MW', 0, 2, 1, 0, 2),
('WA', 18, 20, 'BX', 1, 'B', 'JW', 0, 2, 1, 0, 2),
('WA', 0, 17, 'BX', 0, 'B', 'CM', 0, 2, 1, 0, 2),
('WA', 21, 49, 'BX', 0, 'B', 'M', 1, 2, 1, 0, 2),
('WA', 0, 17, 'BX', 1, 'B', 'CW', 0, 2, 1, 0, 2),
('WA', 0, 17, 'BJX', 0, 'B', 'CM', 0, 2, 1, 0, 2),
('WA', 0, 17, 'BJX', 1, 'B', 'CW', 0, 2, 1, 0, 2),
('WA', 18, 20, 'BJX', 0, 'B', 'JM', 1, 2, 1, 0, 2),
('WA', 18, 20, 'BJX', 1, 'B', 'JW', 1, 2, 1, 0, 2),
('WA', 0, 17, 'BCX', 1, 'B', 'CW', 1, 2, 1, 0, 2),
('WA', 0, 17, 'BCX', 0, 'B', 'CM', 1, 2, 1, 0, 2),
('WA', 50, 100, 'BMX', 0, 'B', 'MM', 1, 2, 1, 0, 2),
('WA', 50, 100, 'BMX', 1, 'B', 'MW', 1, 2, 1, 0, 2),
('WA', 0, 100, 'RMO', 0, 'R', 'M', 1, 3, 0, 1, 3),
('WA', 0, 100, 'RWO', 1, 'R', 'W', 1, 3, 0, 1, 3),
('WA', 0, 100, 'CMO', 0, 'C', 'M', 1, 3, 0, 1, 3),
('WA', 0, 100, 'CWO', 1, 'C', 'W', 1, 3, 0, 1, 3),
('WA', 0, 100, 'MW1', 0, 'W1', 'M', 1, 3, 0, 1, 3),
('WA', 0, 100, 'WW1', 1, 'W1', 'W', 1, 3, 0, 1, 3),
('WA', 0, 100, 'W1X', 0, 'W1', 'M', 1, 2, 1, 1, 2),
('WA', 0, 100, 'W1X', 1, 'W1', 'W', 1, 2, 1, 1, 2),
('WA', 50, 100, 'RMX', 0, 'R', 'MM', 1, 2, 1, 0, 2),
('WA', 50, 100, 'RMX', 1, 'R', 'MW', 1, 2, 1, 0, 2),
('WA', 50, 100, 'CMX', 0, 'C', 'MM', 1, 2, 1, 0, 2),
('WA', 50, 100, 'CMX', 1, 'C', 'MW', 1, 2, 1, 0, 2)";

safe_w_sql($ClEquivSQL);
