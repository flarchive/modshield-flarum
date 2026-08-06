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
      help: app.translator.trans('modshield-flarum.admin.mode_help'),
      type: 'select',
      options: {
        disabled: 'Disabled',
        active: 'Active (enforce ModShield Core decisions)',
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
    })
    .registerSetting({
      setting: 'modshield.callback_secret',
      label: app.translator.trans('modshield-flarum.admin.callback_secret_label'),
      help: app.translator.trans('modshield-flarum.admin.callback_secret_help', {
        url: app.forum.attribute('apiUrl') + '/modshield/callback',
      }),
      type: 'password',
      placeholder: 'whsec_...',
    });
});
