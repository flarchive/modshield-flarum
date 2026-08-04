import app from 'flarum/admin/app';

app.initializers.add('modshield-flarum', () => {
  app.extensionData
    .for('modshield-flarum')
    .registerSetting({
      setting: 'modshield.core_url',
      label: app.translator.trans('modshield-flarum.admin.core_url_label'),
      type: 'text',
      placeholder: 'https://modshield.example.com',
    })
    .registerSetting({
      setting: 'modshield.api_key',
      label: app.translator.trans('modshield-flarum.admin.api_key_label'),
      type: 'text',
      placeholder: 'ms_live_...',
    })
    .registerSetting({
      setting: 'modshield.mode',
      label: app.translator.trans('modshield-flarum.admin.mode_label'),
      type: 'select',
      options: {
        disabled: 'Disabled',
        shadow: 'Shadow (observe only)',
        active: 'Active (enforce decisions)',
      },
      default: 'disabled',
    })
    .registerSetting({
      setting: 'modshield.fail_strategy',
      label: app.translator.trans('modshield-flarum.admin.fail_strategy_label'),
      type: 'select',
      options: {
        fail_open: 'Allow posts (fail open)',
        fail_closed: 'Hold for review (fail closed)',
      },
      default: 'fail_open',
    })
    .registerSetting({
      setting: 'modshield.moderator_group_id',
      label: app.translator.trans('modshield-flarum.admin.moderator_group_id_label'),
      help: app.translator.trans('modshield-flarum.admin.moderator_group_id_help'),
      type: 'number',
      placeholder: '4',
    });
});
