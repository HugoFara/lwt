-- Rename theme directories: old names -> new descriptive names
UPDATE settings SET StValue = 'assets/themes/Flex_Wrap/'
    WHERE StKey = 'set-theme-dir' AND StValue = 'assets/themes/Default_Mod/';

UPDATE settings SET StValue = 'assets/themes/Dark/'
    WHERE StKey = 'set-theme-dir' AND StValue = 'assets/themes/Night_Mode/';

UPDATE settings SET StValue = 'assets/themes/Dark_Muted/'
    WHERE StKey = 'set-theme-dir' AND StValue = 'assets/themes/White_Night/';

UPDATE settings SET StValue = 'assets/themes/Underline/'
    WHERE StKey = 'set-theme-dir' AND StValue = 'assets/themes/Lingocracy/';

UPDATE settings SET StValue = 'assets/themes/Underline_Dark/'
    WHERE StKey = 'set-theme-dir' AND StValue = 'assets/themes/Lingocracy_Dark/';
