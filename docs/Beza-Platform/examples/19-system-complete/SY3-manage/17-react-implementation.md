# 17 - تطبيق React: صفحة إدارة النظام مع تبويبات (React Admin System Management Page with Tabs)

<div dir="rtl">

## صفحة إدارة النظام في React

```tsx
// src/pages/admin/SystemManagement.tsx

import React, { useState, useEffect, useCallback } from 'react';
import {
  Box,
  Tabs,
  Tab,
  Card,
  CardContent,
  Typography,
  Button,
  IconButton,
  List,
  ListItem,
  ListItemText,
  ListItemIcon,
  ListItemSecondaryAction,
  Switch,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogContentText,
  DialogActions,
  Snackbar,
  Alert,
  CircularProgress,
  Chip,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Tooltip,
  Divider,
  Grid,
} from '@mui/material';
import {
  Refresh as RefreshIcon,
  DeleteSweep as DeleteSweepIcon,
  RestartAlt as RestartAltIcon,
  Storage as StorageIcon,
  Article as ArticleIcon,
  Info as InfoIcon,
  Speed as SpeedIcon,
  CleaningServices as CleaningServicesIcon,
  Shield as ShieldIcon,
  Schedule as ScheduleIcon,
  Download as DownloadIcon,
  Restore as RestoreIcon,
  Delete as DeleteIcon,
  Warning as WarningIcon,
} from '@mui/icons-material';
import { api } from '../../services/api';
import { useAuth } from '../../hooks/useAuth';
import { useNavigate } from 'react-router-dom';

/**
 * واجهات البيانات
 */
interface SystemInfo {
  php: { version: string; memory_limit: string };
  laravel: {
    version: string; environment: string; debug_mode: boolean;
    cache_driver: string; queue_driver: string;
  };
  disk: { total_space: string; free_space: string; usage_percent: number };
}

interface Backup {
  filename: string; size: number;
  size_formatted: string; created_at: string;
}

interface LogFile {
  name: string; size: number;
  size_formatted: string; modified: string;
}

interface QueueStatus {
  driver: string; pending: number; failed: number;
}

interface ScheduledTask {
  command: string; expression: string; readable: string;
}

/**
 * واجهة التبويب
 */
interface TabPanelProps {
  children: React.ReactNode;
  value: number;
  index: number;
}

function TabPanel({ children, value, index }: TabPanelProps) {
  return (
    <div role="tabpanel" hidden={value !== index}>
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  );
}

/**
 * الصفحة الرئيسية لإدارة النظام
 */
const SystemManagement: React.FC = () => {
  const { user } = useAuth();
  const navigate = useNavigate();
  const [tabValue, setTabValue] = useState(0);
  const [loading, setLoading] = useState(false);
  const [snackbar, setSnackbar] = useState<{
    open: boolean; message: string; severity: 'success' | 'error';
  }>({ open: false, message: '', severity: 'success' });

  // بيانات التبويبات
  const [systemInfo, setSystemInfo] = useState<SystemInfo | null>(null);
  const [backups, setBackups] = useState<Backup[]>([]);
  const [logFiles, setLogFiles] = useState<LogFile[]>([]);
  const [queueStatus, setQueueStatus] = useState<QueueStatus | null>(null);
  const [scheduledTasks, setScheduledTasks] = useState<ScheduledTask[]>([]);

  // حوارات التأكيد
  const [confirmDialog, setConfirmDialog] = useState<{
    open: boolean; title: string; message: string;
    action: () => void; danger?: boolean;
  }>({ open: false, title: '', message: '', action: () => {} });

  /**
   * عرض رسالة
   */
  const showMessage = (message: string, severity: 'success' | 'error') => {
    setSnackbar({ open: true, message, severity });
  };

  /**
   * إظهار حوار تأكيد
   */
  const showConfirm = (
    title: string, message: string,
    action: () => void, danger = false
  ) => {
    setConfirmDialog({ open: true, title, message, action, danger });
  };

  // ========== جلب البيانات ==========

  const fetchSystemInfo = useCallback(async () => {
    const res = await api.get('/admin/system/info');
    setSystemInfo(res.data.data);
  }, []);

  const fetchBackups = useCallback(async () => {
    const res = await api.get('/admin/system/backup/list');
    setBackups(res.data.data);
  }, []);

  const fetchLogFiles = useCallback(async () => {
    const res = await api.get('/admin/system/logs');
    setLogFiles(res.data.data);
  }, []);

  const fetchQueueStatus = useCallback(async () => {
    const res = await api.get('/admin/system/queue/status');
    setQueueStatus(res.data.data);
  }, []);

  const fetchScheduledTasks = useCallback(async () => {
    const res = await api.get('/admin/system/schedule');
    setScheduledTasks(res.data.data);
  }, []);

  /**
   * تحميل جميع البيانات
   */
  const loadAllData = useCallback(async () => {
    setLoading(true);
    try {
      await Promise.all([
        fetchSystemInfo(), fetchBackups(),
        fetchLogFiles(), fetchQueueStatus(),
        fetchScheduledTasks(),
      ]);
    } catch (err: any) {
      showMessage(err.response?.data?.message || 'فشل تحميل البيانات', 'error');
    } finally {
      setLoading(false);
    }
  }, [fetchSystemInfo, fetchBackups, fetchLogFiles,
      fetchQueueStatus, fetchScheduledTasks]);

  useEffect(() => { loadAllData(); }, [loadAllData]);

  // ========== عمليات الإدارة ==========

  /**
   * مسح الكاش
   */
  const handleClearCache = async () => {
    showConfirm(
      'تأكيد مسح الكاش',
      'هل أنت متأكد من مسح جميع أنواع الذاكرة المؤقتة؟',
      async () => {
        try {
          const res = await api.post('/admin/system/cache/clear');
          showMessage(res.data.message, 'success');
        } catch (err: any) {
          showMessage(err.response?.data?.message || 'فشل مسح الكاش', 'error');
        }
      }
    );
  };

  /**
   * تحسين الكاش
   */
  const handleOptimizeCache = async () => {
    try {
      const res = await api.post('/admin/system/cache/optimize');
      showMessage(res.data.message, 'success');
    } catch (err: any) {
      showMessage(err.response?.data?.message || 'فشل تحسين الكاش', 'error');
    }
  };

  /**
   * إنشاء نسخة احتياطية
   */
  const handleCreateBackup = async () => {
    setLoading(true);
    try {
      const res = await api.post('/admin/system/backup');
      showMessage(res.data.message, 'success');
      await fetchBackups();
    } catch (err: any) {
      showMessage(err.response?.data?.message || 'فشل إنشاء النسخة', 'error');
    } finally {
      setLoading(false);
    }
  };

  /**
   * استعادة نسخة احتياطية
   */
  const handleRestoreBackup = (backup: Backup) => {
    showConfirm(
      '⚠️ تأكيد استعادة النسخة الاحتياطية',
      `هل أنت متأكد من استعادة قاعدة البيانات من النسخة "${backup.filename}"؟\n\n`
      + 'سيتم فقدان جميع التغييرات منذ آخر نسخة احتياطية.',
      async () => {
        try {
          const res = await api.post(
            `/admin/system/backup/${backup.filename}/restore`,
            { confirm: true }
          );
          showMessage(res.data.message, 'success');
        } catch (err: any) {
          showMessage(
            err.response?.data?.message || 'فشل استعادة النسخة', 'error'
          );
        }
      },
      true
    );
  };

  /**
   * حذف نسخة احتياطية
   */
  const handleDeleteBackup = (backup: Backup) => {
    showConfirm(
      'تأكيد الحذف',
      `هل أنت متأكد من حذف "${backup.filename}"؟`,
      async () => {
        try {
          await api.delete(`/admin/system/backup/${backup.filename}`);
          showMessage('تم حذف النسخة الاحتياطية', 'success');
          await fetchBackups();
        } catch (err: any) {
          showMessage(
            err.response?.data?.message || 'فشل حذف النسخة', 'error'
          );
        }
      },
      true
    );
  };

  /**
   * مسح السجلات
   */
  const handleClearLogs = () => {
    showConfirm(
      'تأكيد مسح السجلات',
      'هل أنت متأكد من مسح جميع ملفات السجل؟',
      async () => {
        try {
          await api.post('/admin/system/log/clear');
          showMessage('تم مسح ملفات السجل', 'success');
          await fetchLogFiles();
        } catch (err: any) {
          showMessage(err.response?.data?.message || 'فشل مسح السجلات', 'error');
        }
      }
    );
  };

  /**
   * إعادة تشغيل قائمة الانتظار
   */
  const handleRestartQueue = async () => {
    try {
      await api.post('/admin/system/queue/restart');
      showMessage('تم إعادة تشغيل عمال قائمة الانتظار', 'success');
    } catch (err: any) {
      showMessage(
        err.response?.data?.message || 'فشل إعادة التشغيل', 'error'
      );
    }
  };

  /**
   * تبديل وضع الصيانة
   */
  const handleToggleMaintenance = async (enabled: boolean) => {
    const action = enabled ? 'تفعيل' : 'تعطيل';
    showConfirm(
      `تأكيد ${action} وضع الصيانة`,
      `هل أنت متأكد من ${action} وضع الصيانة؟`,
      async () => {
        try {
          const res = await api.post('/admin/system/maintenance', { enabled });
          showMessage(res.data.message, 'success');
          await fetchSystemInfo();
        } catch (err: any) {
          showMessage(err.response?.data?.message || 'فشل التبديل', 'error');
        }
      },
      enabled
    );
  };

  /**
   * عرض محتوى ملف سجل
   */
  const handleViewLog = async (filename: string) => {
    try {
      const res = await api.get(`/admin/system/logs/${filename}`);
      const content = res.data.data.content || '';
      // فتح نافذة جديدة أو modal لعرض المحتوى
      window.open(`data:text/plain;charset=utf-8,${encodeURIComponent(content)}`);
    } catch (err: any) {
      showMessage('فشل قراءة ملف السجل', 'error');
    }
  };

  if (loading && !systemInfo) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', p: 5 }}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box dir="rtl">
      {/* شريط التبويبات */}
      <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
        <Tabs
          value={tabValue}
          onChange={(_, v) => setTabValue(v)}
          textColor="primary"
          indicatorColor="primary"
        >
          <Tab icon={<InfoIcon />} label="النظام" />
          <Tab icon={<StorageIcon />} label="النسخ الاحتياطي" />
          <Tab icon={<ArticleIcon />} label="السجلات" />
          <Tab icon={<ScheduleIcon />} label="قائمة الانتظار" />
        </Tabs>
      </Box>

      {/* تبويب: النظام */}
      <TabPanel value={tabValue} index={0}>
        <Grid container spacing={3}>
          {/* معلومات النظام */}
          <Grid item xs={12} md={8}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>معلومات النظام</Typography>
                <Divider sx={{ mb: 2 }} />
                <Grid container spacing={2}>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">PHP</Typography>
                    <Typography variant="body1">
                      {systemInfo?.php.version || '---'}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">Laravel</Typography>
                    <Typography variant="body1">
                      {systemInfo?.laravel.version || '---'}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">البيئة</Typography>
                    <Chip
                      label={systemInfo?.laravel.environment || '---'}
                      color={
                        systemInfo?.laravel.environment === 'production'
                          ? 'primary' : 'default'
                      }
                      size="small"
                    />
                  </Grid>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">Cache Driver</Typography>
                    <Typography variant="body1">
                      {systemInfo?.laravel.cache_driver || '---'}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">Queue Driver</Typography>
                    <Typography variant="body1">
                      {systemInfo?.laravel.queue_driver || '---'}
                    </Typography>
                  </Grid>
                  <Grid item xs={6}>
                    <Typography color="textSecondary">Debug Mode</Typography>
                    <Chip
                      label={systemInfo?.laravel.debug_mode ? 'ON' : 'OFF'}
                      color={systemInfo?.laravel.debug_mode ? 'warning' : 'success'}
                      size="small"
                    />
                  </Grid>
                </Grid>
              </CardContent>
            </Card>
          </Grid>

          {/* أزرار الإجراءات */}
          <Grid item xs={12} md={4}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>الإجراءات</Typography>
                <Divider sx={{ mb: 2 }} />
                <Button
                  fullWidth variant="contained" sx={{ mb: 1 }}
                  startIcon={<CleaningServicesIcon />}
                  onClick={handleClearCache}
                >
                  مسح الكاش
                </Button>
                <Button
                  fullWidth variant="outlined" sx={{ mb: 1 }}
                  startIcon={<SpeedIcon />}
                  onClick={handleOptimizeCache}
                >
                  تحسين الكاش
                </Button>
                <Box sx={{ mt: 2 }}>
                  <Typography gutterBottom>وضع الصيانة</Typography>
                  <Switch
                    checked={systemInfo?.laravel.environment === 'maintenance'}
                    onChange={(_, checked) => handleToggleMaintenance(checked)}
                    color="warning"
                  />
                  <Typography variant="caption" color="textSecondary">
                    {systemInfo?.laravel.environment === 'maintenance'
                      ? 'التطبيق في وضع الصيانة'
                      : 'التطبيق يعمل بشكل طبيعي'}
                  </Typography>
                </Box>
              </CardContent>
            </Card>

            {/* معلومات القرص */}
            <Card sx={{ mt: 2 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>القرص الصلب</Typography>
                <Divider sx={{ mb: 2 }} />
                <Typography>
                  المساحة المستخدمة: {systemInfo?.disk.usage_percent}%
                </Typography>
                <Typography>
                  المساحة الحرة: {systemInfo?.disk.free_space}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      </TabPanel>

      {/* تبويب: النسخ الاحتياطي */}
      <TabPanel value={tabValue} index={1}>
        <Button
          variant="contained" color="primary" sx={{ mb: 3 }}
          startIcon={<DownloadIcon />}
          onClick={handleCreateBackup}
          disabled={loading}
        >
          إنشاء نسخة احتياطية جديدة
        </Button>

        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>اسم الملف</TableCell>
                <TableCell>الحجم</TableCell>
                <TableCell>تاريخ الإنشاء</TableCell>
                <TableCell align="left">الإجراءات</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {backups.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} align="center">
                    لا توجد نسخ احتياطية
                  </TableCell>
                </TableRow>
              ) : (
                backups.map((backup) => (
                  <TableRow key={backup.filename}>
                    <TableCell>{backup.filename}</TableCell>
                    <TableCell>{backup.size_formatted}</TableCell>
                    <TableCell>{backup.created_at}</TableCell>
                    <TableCell>
                      <Tooltip title="استعادة">
                        <IconButton
                          color="warning"
                          onClick={() => handleRestoreBackup(backup)}
                        >
                          <RestoreIcon />
                        </IconButton>
                      </Tooltip>
                      <Tooltip title="حذف">
                        <IconButton
                          color="error"
                          onClick={() => handleDeleteBackup(backup)}
                        >
                          <DeleteIcon />
                        </IconButton>
                      </Tooltip>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </TabPanel>

      {/* تبويب: السجلات */}
      <TabPanel value={tabValue} index={2}>
        <Button
          variant="outlined" color="error" sx={{ mb: 3 }}
          startIcon={<DeleteSweepIcon />}
          onClick={handleClearLogs}
        >
          مسح جميع ملفات السجل
        </Button>

        <TableContainer component={Paper}>
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>اسم الملف</TableCell>
                <TableCell>الحجم</TableCell>
                <TableCell>آخر تعديل</TableCell>
                <TableCell>الإجراءات</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {logFiles.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={4} align="center">
                    لا توجد ملفات سجل
                  </TableCell>
                </TableRow>
              ) : (
                logFiles.map((log) => (
                  <TableRow key={log.name}>
                    <TableCell>{log.name}</TableCell>
                    <TableCell>{log.size_formatted}</TableCell>
                    <TableCell>{log.modified}</TableCell>
                    <TableCell>
                      <Button
                        size="small" variant="text"
                        onClick={() => handleViewLog(log.name)}
                      >
                        عرض
                      </Button>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </TabPanel>

      {/* تبويب: قائمة الانتظار */}
      <TabPanel value={tabValue} index={3}>
        <Grid container spacing={3}>
          {/* حالة قائمة الانتظار */}
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>حالة قائمة الانتظار</Typography>
                <Divider sx={{ mb: 2 }} />
                <Typography>Driver: {queueStatus?.driver || '---'}</Typography>
                <Typography>مهام معلقة: {queueStatus?.pending || 0}</Typography>
                <Typography>مهام فاشلة: {queueStatus?.failed || 0}</Typography>
                <Button
                  variant="contained" color="warning" sx={{ mt: 2 }}
                  startIcon={<RestartAltIcon />}
                  onClick={handleRestartQueue}
                >
                  إعادة تشغيل العمال
                </Button>
              </CardContent>
            </Card>
          </Grid>

          {/* المهام المجدولة */}
          <Grid item xs={12} md={6}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom>المهام المجدولة</Typography>
                <Divider sx={{ mb: 2 }} />
                {scheduledTasks.length === 0 ? (
                  <Typography color="textSecondary">
                    لا توجد مهام مجدولة
                  </Typography>
                ) : (
                  <List dense>
                    {scheduledTasks.map((task, i) => (
                      <ListItem key={i}>
                        <ListItemIcon>
                          <ScheduleIcon color="action" />
                        </ListItemIcon>
                        <ListItemText
                          primary={task.command}
                          secondary={task.readable}
                        />
                      </ListItem>
                    ))}
                  </List>
                )}
              </CardContent>
            </Card>
          </Grid>
        </Grid>
      </TabPanel>

      {/* حوار التأكيد */}
      <Dialog
        open={confirmDialog.open}
        onClose={() => setConfirmDialog(prev => ({ ...prev, open: false }))}
      >
        <DialogTitle>
          {confirmDialog.danger && (
            <WarningIcon color="error" sx={{ verticalAlign: 'middle', mr: 1 }} />
          )}
          {confirmDialog.title}
        </DialogTitle>
        <DialogContent>
          <DialogContentText>{confirmDialog.message}</DialogContentText>
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setConfirmDialog(prev => ({ ...prev, open: false }))}>
            إلغاء
          </Button>
          <Button
            onClick={() => {
              confirmDialog.action();
              setConfirmDialog(prev => ({ ...prev, open: false }));
            }}
            color={confirmDialog.danger ? 'error' : 'primary'}
            variant="contained"
          >
            تأكيد
          </Button>
        </DialogActions>
      </Dialog>

      {/* Snackbar للإشعارات */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={4000}
        onClose={() => setSnackbar(prev => ({ ...prev, open: false }))}
        anchorOrigin={{ vertical: 'top', horizontal: 'center' }}
      >
        <Alert
          severity={snackbar.severity}
          onClose={() => setSnackbar(prev => ({ ...prev, open: false }))}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
};

export default SystemManagement;
```

## خدمة API

```typescript
// src/services/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api/v1',
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
});

// إضافة Bearer token تلقائياً
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('jwt_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// معالجة الأخطاء
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('jwt_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export { api };
```

## هوك المصادقة

```typescript
// src/hooks/useAuth.ts
import { useContext } from 'react';
import { AuthContext } from '../contexts/AuthContext';

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
```

</div>
