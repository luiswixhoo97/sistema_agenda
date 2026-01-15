package com.beautyspa.app;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.content.Context;
import android.os.Build;
import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    private static final String CHANNEL_ID = "beautyspa_citas";
    private static final String CHANNEL_NAME = "Citas BeautySpa";
    private static final String CHANNEL_DESCRIPTION = "Notificaciones de citas y recordatorios";

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Crear canal de notificaciones para Android 8.0+ (API 26+)
        createNotificationChannel();
    }

    /**
     * Crea el canal de notificaciones necesario para Android 8.0+
     * Este canal permite que las notificaciones se muestren como notificaciones del sistema
     */
    private void createNotificationChannel() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            NotificationManager notificationManager = 
                (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);

            // Verificar si el canal ya existe
            if (notificationManager.getNotificationChannel(CHANNEL_ID) == null) {
                NotificationChannel channel = new NotificationChannel(
                    CHANNEL_ID,
                    CHANNEL_NAME,
                    NotificationManager.IMPORTANCE_HIGH
                );
                
                channel.setDescription(CHANNEL_DESCRIPTION);
                channel.enableLights(true);
                channel.enableVibration(true);
                channel.setShowBadge(true);
                
                // Configurar sonido por defecto
                channel.setSound(
                    android.media.RingtoneManager.getDefaultUri(
                        android.media.RingtoneManager.TYPE_NOTIFICATION
                    ),
                    null
                );
                
                // Registrar el canal
                notificationManager.createNotificationChannel(channel);
            }
        }
    }
}
