import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { roleApi, permissionApi, Role, Permission } from '../../services/api';

interface FormData {
  name: string;
  description: string;
  is_system: boolean;
  permission_ids: string[];
}

interface ModuleGroup {
  module: string;
  permissions: Permission[];
}

const RoleForm: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isEdit = Boolean(id);

  const [formData, setFormData] = useState<FormData>({
    name: '',
    description: '',
    is_system: false,
    permission_ids: [],
  });
  const [allPermissions, setAllPermissions] = useState<Permission[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [validationErrors, setValidationErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const [permResult] = await Promise.all([
          permissionApi.list({ per_page: 500 }),
        ]);
        setAllPermissions(permResult.data);

        if (isEdit && id) {
          const role = await roleApi.show(id);
          setFormData({
            name: role.name,
            description: role.description || '',
            is_system: role.is_system,
            permission_ids: role.permissions?.map((p) => p.id) || [],
          });
        }
      } catch {
        setError('فشل في تحميل البيانات');
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [id, isEdit]);

  const handleChange = (field: keyof FormData, value: string | boolean | string[]) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    if (validationErrors[field]) {
      setValidationErrors((prev) => {
        const copy = { ...prev };
        delete copy[field];
        return copy;
      });
    }
  };

  const handleTogglePermission = (permId: string) => {
    setFormData((prev) => {
      const exists = prev.permission_ids.includes(permId);
      return {
        ...prev,
        permission_ids: exists
          ? prev.permission_ids.filter((id) => id !== permId)
          : [...prev.permission_ids, permId],
      };
    });
  };

  const handleSelectModule = (module: string, permissions: Permission[]) => {
    const permIds = permissions.map((p) => p.id);
    const allSelected = permIds.every((id) => formData.permission_ids.includes(id));
    setFormData((prev) => ({
      ...prev,
      permission_ids: allSelected
        ? prev.permission_ids.filter((id) => !permIds.includes(id))
        : [...new Set([...prev.permission_ids, ...permIds])],
    }));
  };

  const validate = (): boolean => {
    const errors: Record<string, string> = {};
    if (!formData.name.trim()) {
      errors.name = 'اسم الدور مطلوب';
    }
    if (formData.permission_ids.length === 0) {
      errors.permissions = 'يجب اختيار صلاحية واحدة على الأقل';
    }
    setValidationErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate()) return;

    setSaving(true);
    setError(null);
    try {
      const payload = {
        name: formData.name,
        description: formData.description,
        permission_ids: formData.permission_ids,
      };

      if (isEdit && id) {
        await roleApi.update(id, payload);
        await roleApi.assignPermissions(id, formData.permission_ids);
      } else {
        const created = await roleApi.create(payload);
        await roleApi.assignPermissions(created.id, formData.permission_ids);
      }
      navigate('/admin/roles');
    } catch {
      setError('فشل في حفظ الدور');
    } finally {
      setSaving(false);
    }
  };

  const groupedPermissions: ModuleGroup[] = React.useMemo(() => {
    const groups: Record<string, Permission[]> = {};
    for (const perm of allPermissions) {
      if (!groups[perm.module]) groups[perm.module] = [];
      groups[perm.module].push(perm);
    }
    return Object.entries(groups).map(([module, permissions]) => ({
      module,
      permissions,
    }));
  }, [allPermissions]);

  if (loading) {
    return (
      <div style={styles.centerState}>
        <div style={styles.spinner} />
        <p>جاري التحميل...</p>
      </div>
    );
  }

  if (error && !saving) {
    return (
      <div style={styles.centerState}>
        <p style={{ color: '#C62828' }}>{error}</p>
        <button onClick={() => navigate('/admin/roles')} style={styles.retryBtn}>
          العودة
        </button>
      </div>
    );
  }

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h1 style={styles.title}>
          {isEdit ? 'تعديل الدور' : 'إضافة دور جديد'}
        </h1>
      </div>

      <form onSubmit={handleSubmit} style={styles.form}>
        <div style={styles.formSection}>
          <div style={styles.fieldGroup}>
            <label style={styles.label}>اسم الدور</label>
            <input
              type="text"
              value={formData.name}
              onChange={(e) => handleChange('name', e.target.value)}
              style={{
                ...styles.input,
                borderColor: validationErrors.name ? '#C62828' : '#E0E0E0',
              }}
              disabled={formData.is_system}
              placeholder="مثال: مشرف مالي"
            />
            {validationErrors.name && (
              <span style={styles.errorText}>{validationErrors.name}</span>
            )}
          </div>

          <div style={styles.fieldGroup}>
            <label style={styles.label}>الوصف</label>
            <textarea
              value={formData.description}
              onChange={(e) => handleChange('description', e.target.value)}
              style={styles.textarea}
              rows={3}
              placeholder="وصف الدور..."
              disabled={formData.is_system}
            />
          </div>

          {formData.is_system && (
            <div style={styles.notice}>
              هذا دور نظام ولا يمكن تعديل اسمه أو صلاحياته
            </div>
          )}
        </div>

        <div style={styles.formSection}>
          <div style={styles.sectionHeader}>
            <h2 style={styles.sectionTitle}>الصلاحيات</h2>
            {validationErrors.permissions && (
              <span style={styles.errorText}>{validationErrors.permissions}</span>
            )}
          </div>

          <div style={styles.permissionsGrid}>
            {groupedPermissions.map((group) => {
              const allSelected = group.permissions.every((p) =>
                formData.permission_ids.includes(p.id)
              );
              const someSelected = group.permissions.some((p) =>
                formData.permission_ids.includes(p.id)
              );
              return (
                <div key={group.module} style={styles.moduleCard}>
                  <div
                    style={styles.moduleHeader}
                    onClick={() => {
                      if (!formData.is_system) handleSelectModule(group.module, group.permissions);
                    }}
                  >
                    <ModuleCheckbox
                      checked={allSelected}
                      indeterminate={!allSelected && someSelected}
                      onChange={() => {
                        if (!formData.is_system) handleSelectModule(group.module, group.permissions);
                      }}
                    />
                    <span style={styles.moduleName}>{group.module}</span>
                    <span style={styles.moduleCount}>
                      {group.permissions.length}
                    </span>
                  </div>
                  <div style={styles.permissionList}>
                    {group.permissions.map((perm) => (
                      <label
                        key={perm.id}
                        style={{
                          ...styles.permissionItem,
                          opacity: formData.is_system ? 0.6 : 1,
                        }}
                      >
                        <input
                          type="checkbox"
                          checked={formData.permission_ids.includes(perm.id)}
                          onChange={() => {
                            if (!formData.is_system) handleTogglePermission(perm.id);
                          }}
                          style={styles.checkbox}
                          disabled={formData.is_system}
                        />
                        <div>
                          <span style={styles.permissionName}>{perm.name}</span>
                          {perm.description && (
                            <span style={styles.permissionDesc}>{perm.description}</span>
                          )}
                        </div>
                      </label>
                    ))}
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {error && (
          <div style={styles.formError}>
            <p>{error}</p>
          </div>
        )}

        <div style={styles.formActions}>
          <button
            type="button"
            onClick={() => navigate('/admin/roles')}
            style={styles.cancelBtn}
          >
            إلغاء
          </button>
          <button
            type="submit"
            disabled={saving || formData.is_system}
            style={{
              ...styles.submitBtn,
              opacity: saving || formData.is_system ? 0.6 : 1,
            }}
          >
            {saving ? 'جاري الحفظ...' : isEdit ? 'حفظ التغييرات' : 'إنشاء الدور'}
          </button>
        </div>
      </form>
    </div>
  );
};

const ModuleCheckbox: React.FC<{
  checked: boolean;
  indeterminate: boolean;
  onChange: () => void;
}> = ({ checked, indeterminate, onChange }) => {
  const ref = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (ref.current) {
      ref.current.indeterminate = indeterminate;
    }
  }, [indeterminate]);

  return (
    <input
      ref={ref}
      type="checkbox"
      checked={checked}
      style={styles.moduleCheckbox}
      onClick={(e) => e.stopPropagation()}
      onChange={onChange}
    />
  );
};

const styles: Record<string, React.CSSProperties> = {
  container: {
    direction: 'rtl',
    padding: '24px',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
    maxWidth: '900px',
    margin: '0 auto',
  },
  header: {
    marginBottom: '24px',
  },
  title: {
    fontSize: '24px',
    fontWeight: 700,
    color: '#1B5E20',
    margin: 0,
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '24px',
  },
  formSection: {
    backgroundColor: '#fff',
    borderRadius: '12px',
    padding: '24px',
    border: '1px solid #E0E0E0',
  },
  fieldGroup: {
    marginBottom: '20px',
  },
  label: {
    display: 'block',
    fontSize: '14px',
    fontWeight: 600,
    color: '#424242',
    marginBottom: '8px',
  },
  input: {
    width: '100%',
    padding: '12px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    outline: 'none',
    boxSizing: 'border-box',
  },
  textarea: {
    width: '100%',
    padding: '12px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    outline: 'none',
    resize: 'vertical',
    boxSizing: 'border-box',
    fontFamily: 'inherit',
  },
  errorText: {
    color: '#C62828',
    fontSize: '12px',
    marginTop: '4px',
    display: 'block',
  },
  notice: {
    padding: '12px 16px',
    backgroundColor: '#FFF3E0',
    borderRadius: '8px',
    color: '#E65100',
    fontSize: '14px',
  },
  sectionHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '16px',
  },
  sectionTitle: {
    fontSize: '18px',
    fontWeight: 700,
    color: '#212121',
    margin: 0,
  },
  permissionsGrid: {
    display: 'flex',
    flexDirection: 'column',
    gap: '16px',
  },
  moduleCard: {
    border: '1px solid #E0E0E0',
    borderRadius: '10px',
    overflow: 'hidden',
  },
  moduleHeader: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '14px 16px',
    backgroundColor: '#F5F5F5',
    cursor: 'pointer',
    borderBottom: '1px solid #E0E0E0',
  },
  moduleCheckbox: {
    width: '18px',
    height: '18px',
    cursor: 'pointer',
  },
  moduleName: {
    fontSize: '15px',
    fontWeight: 600,
    color: '#212121',
    flex: 1,
  },
  moduleCount: {
    fontSize: '12px',
    color: '#9E9E9E',
    backgroundColor: '#EEEEEE',
    padding: '2px 10px',
    borderRadius: '12px',
  },
  permissionList: {
    padding: '8px',
  },
  permissionItem: {
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '10px 12px',
    borderRadius: '6px',
    cursor: 'pointer',
    transition: 'background-color 0.15s',
  },
  checkbox: {
    width: '16px',
    height: '16px',
    cursor: 'pointer',
  },
  permissionName: {
    display: 'block',
    fontSize: '14px',
    color: '#212121',
    fontWeight: 500,
  },
  permissionDesc: {
    display: 'block',
    fontSize: '12px',
    color: '#9E9E9E',
    marginTop: '2px',
  },
  formError: {
    padding: '12px 16px',
    backgroundColor: '#FFEBEE',
    borderRadius: '8px',
    color: '#C62828',
    fontSize: '14px',
  },
  formActions: {
    display: 'flex',
    justifyContent: 'flex-end',
    gap: '12px',
    paddingTop: '8px',
  },
  cancelBtn: {
    padding: '12px 24px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    backgroundColor: '#fff',
    color: '#616161',
    fontSize: '14px',
    cursor: 'pointer',
  },
  submitBtn: {
    padding: '12px 24px',
    borderRadius: '8px',
    border: 'none',
    backgroundColor: '#2E7D32',
    color: '#fff',
    fontSize: '14px',
    fontWeight: 600,
    cursor: 'pointer',
  },
  centerState: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '80px 0',
    color: '#757575',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
  },
  spinner: {
    width: '40px',
    height: '40px',
    border: '4px solid #E0E0E0',
    borderTop: '4px solid #2E7D32',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
    marginBottom: '16px',
  },
  retryBtn: {
    padding: '10px 24px',
    borderRadius: '8px',
    border: 'none',
    backgroundColor: '#2E7D32',
    color: '#fff',
    fontSize: '14px',
    cursor: 'pointer',
  },
};

export default RoleForm;
